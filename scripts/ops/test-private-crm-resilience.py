#!/usr/bin/env python3
"""Negative trial/OTP and service recovery tests for this isolated stack only."""
import importlib.util
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import time
import uuid

spec=importlib.util.spec_from_file_location('private_http',Path(__file__).with_name('test-private-crm-http.py'))
http=importlib.util.module_from_spec(spec);spec.loader.exec_module(http)
compose=['docker','compose','--env-file',os.environ['CRM_PRIVATE_ENV_FILE'],'-p',os.environ['CRM_PRIVATE_PROJECT'],
         '-f',str(http.ROOT/'infra/homologation/compose.yaml')]

def run(arguments):
    result=subprocess.run(compose+arguments,capture_output=True)
    http.check(result.returncode==0,'isolated service operation failed')

def main():
    http.scenario('clear-cache')
    client=http.Client(); account=http.FIXTURES['clientA']
    client.login(account,recovery=account['recovery'][1])
    before=http.leads(client)
    http.scenario('expire-clientA-trial')
    client.get('/painel')
    client.post('/leads',{'title':'LEAD_TESTE_EXPIRED','value_cents':1},expected=403)
    client.get('/painel?section=billing')
    client.post('/pedidos',{'plan':'professional','method':'card','key':str(uuid.uuid4())})
    page=client.get('/painel?section=billing')
    pending=[i for i,row in re.findall(r'<tr data-order-id="([^"]+)">(.*?)</tr>',page,re.S) if 'name="state" value="approved"' in row]
    http.check(len(pending)==1,'expired trial simulated order missing')
    client.post('/pedidos/'+pending[0]+'/simular',{'state':'approved','version':1})
    client.get('/painel');client.post('/leads',{'title':'LEAD_TESTE_NO_ENTITLEMENT','value_cents':1},expected=403)
    http.check(http.leads(client)==before,'simulated payment bypassed expired trial')
    print('PASS expired trial read-only → simulated payment grants no entitlement')

    otp=http.Client(); suffix=secrets.token_hex(4)
    initial=otp.get('/cadastro')
    document=re.search(r'<option[^>]*>([0-9]{14})</option>',initial).group(1)
    login='lab-negative-'+suffix
    http.signup(otp,login,'EMPRESA_TESTE_NEGATIVE_'+suffix.upper(),document,secrets.token_hex(16))
    page=otp.get('/cadastro/codigo')
    code=re.search(r'data-test="otp-code">\s*([0-9]{6})',page).group(1)
    foreign=http.Client();foreign.get('/cadastro/codigo',expected=404)
    wrong='000000' if code!='000000' else '111111'
    for _ in range(5): otp.post('/cadastro/verificar',{'code':wrong})
    _,location=otp.post('/cadastro/verificar',{'code':code})
    http.check(location.endswith('/cadastro/codigo'),'OTP attempt budget bypassed')
    otp.post('/cadastro/reenviar',{});otp.get('/cadastro/codigo')
    otp.post('/cadastro/reenviar',{});otp.get('/cadastro/codigo')
    otp.post('/cadastro/reenviar',{})
    http.check('Limite de três emissões' in otp.get('/cadastro/codigo'),'OTP resend limit missing')
    http.scenario('expire-signup')
    otp.get('/cadastro/codigo',expected=404)
    otp.post('/cadastro/verificar',{'code':code})
    print('PASS private OTP session binding → five-attempt cap → resend cap → expiry')

    duplicate=http.Client(); dup_login='lab-duplicate-'+suffix
    dup_label='EMPRESA_TESTE_DUPLICATE_'+suffix.upper()
    http.signup(duplicate,dup_login,dup_label,document,secrets.token_hex(16))
    page=duplicate.get('/cadastro/codigo')
    dup_code=re.search(r'data-test="otp-code">\s*([0-9]{6})',page).group(1)
    duplicate.post('/cadastro/verificar',{'code':dup_code})
    page=duplicate.get('/cadastro/mfa')
    secret=re.search(r'data-test="setup-secret">\s*([A-Z2-7]{32})',page).group(1)
    duplicate.post('/cadastro/concluir',{'code':http.totp(secret)})
    http.check('Um trial por CNPJ' in duplicate.get('/cadastro/mfa'),'duplicate document obtained second trial')
    administrator=http.Client();administrator.login(dict(http.FIXTURES['admin'],admin=True),recovery=http.FIXTURES['admin']['recovery'][1])
    http.check(dup_label not in administrator.get('/admin'),'duplicate trial created an account')
    print('PASS duplicate CNPJ blocked after OTP and MFA without creating another company')

    try:
        run(['stop','postgres'])
        client.get('/painel',expected=(403,503))
    finally:
        run(['up','-d','--wait','postgres'])
    http.check(http.leads(client)==before,'PostgreSQL recovery lost tenant data')
    run(['restart','app'])
    recovered=False
    for _ in range(20):
        try:
            http.check(http.leads(client)==before,'app restart lost data');recovered=True;break
        except (AssertionError,ConnectionError): time.sleep(0.5)
    http.check(recovered,'application restart did not recover')
    print('PASS isolated PostgreSQL outage fails closed → recovery → app restart preserves records')
    print('PRIVATE RESILIENCE PASS: '+str(http.CHECKS)+' assertions; other applications unchanged.')

if __name__=='__main__': main()
