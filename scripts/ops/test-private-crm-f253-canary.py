#!/usr/bin/env python3
"""F253 pure canary tests; never starts a server or contacts a VPS."""
import importlib.util
from pathlib import Path
import unittest
from unittest.mock import patch

SCRIPT=Path(__file__).with_name("private-crm-f253-canary.py")
spec=importlib.util.spec_from_file_location("private_crm_f253_canary",SCRIPT)
canary=importlib.util.module_from_spec(spec)
spec.loader.exec_module(canary)

HEADERS={"cache-control":"private, no-store",
         "x-robots-tag":"noindex, nofollow"}
SAMPLE='<html><h1>Imagine CRM</h1><section data-home-section="imagine-methodology"></section></html>'
FORM='<form method="get" action="/" data-scenario-form="imagine">' \
    '<input name="visits"><input name="lead_rate"><input name="client_rate"></form>'
BODY=SAMPLE+FORM+'<section data-home-section="imagine-scenario"></section>' \
    +''.join(f'<span data-scenario-{k}="{v}"></span>' for k,v in
            (("visits",2500),("leads",200),("clients",26)))


class F253CanaryTests(unittest.TestCase):
    def reject(self, call):
        with self.assertRaises(RuntimeError):
            call()

    def test_status_cache_robots_and_section_must_be_protected(self):
        canary.check_page(200,HEADERS,SAMPLE,section="imagine-methodology")
        for status in (301,302,401,403,404,500):
            self.reject(lambda s=status:canary.check_page(s,HEADERS,SAMPLE))
        for removed in ("cache-control","x-robots-tag"):
            headers=HEADERS.copy(); del headers[removed]
            self.reject(lambda h=headers:canary.check_page(200,h,SAMPLE))
        self.reject(lambda:canary.check_page(200,HEADERS,SAMPLE,section="imagine-scenario"))
        self.reject(lambda:canary.check_page(200,HEADERS,"generic landing"))

    def test_scenario_evidence_strict_in_three_languages(self):
        for lang,heading in (
            ("pt","Visitas → Leads → Clientes"),
            ("en","Visits → Leads → Customers"),
            ("es","Visitas → Leads → Clientes"),
        ):
            canary.check_scenario(200,HEADERS,BODY+heading,lang)
        for wrong in (
            BODY.replace('data-scenario-leads="200"','data-scenario-leads="201"'),
            BODY.replace('data-scenario-clients="26"','data-scenario-clients="27"'),
            BODY.replace('data-scenario-visits="2500"','data-scenario-visits="2501"'),
            BODY.replace('name="visits"','name="visitors"'),
            BODY.replace('method="get"','method="post"'),
            BODY+'<input name="email">',
            BODY+'<input name="cnpj">',
        ):
            self.reject(lambda text=wrong:canary.check_scenario(
                200,HEADERS,text+"Visitas → Leads → Clientes","pt"))
        self.reject(lambda:canary.check_scenario(
            200,HEADERS,BODY+"Visitas → Leads → Clientes","fr"))

    def test_no_go_does_not_accept_business_success_or_leak_claims(self):
        canary.check_denial(400,"not allowed")
        canary.check_denial(404,"not found")
        for status in (200,201,204,301,302,403,500):
            self.reject(lambda s=status:canary.check_denial(s,"unknown"))
        for material in ("SYNTHETIC_F253_KEY","SYNTHETIC_F253_RESOURCE",
                         "SYNTHETIC_F253_HEADER","access_token"):
            self.reject(lambda m=material:canary.check_denial(404,"body "+m))

    def test_canonical_private_probe_paths_are_fixed(self):
        self.assertEqual(3,len(canary.METHOD_PATHS))
        self.assertTrue(all(p.startswith("/") and not p.startswith("//")
                            for p in canary.METHOD_PATHS))
        self.assertNotIn("/admin",canary.METHOD_PATHS)
        self.assertNotIn("/painel",canary.METHOD_PATHS)

    def test_no_external_access_when_passed_fake_query(self):
        fake_calls=[]
        def fake_query(path,method="GET",headers=None,body=None):
            fake_calls.append((path,method,headers))
            if path.startswith("/?"):
                lang=path.split("lang=")[1].split("&")[0]
                heading=("Visits → Leads → Customers"
                         if lang=="en" else "Visitas → Leads → Clientes")
                return 200,HEADERS,BODY+heading
            if path=="/":
                return 200,HEADERS,SAMPLE
            if path=="/login" and not headers:
                return 200,HEADERS,SAMPLE
            if path in canary.METHOD_PATHS:
                return 404,HEADERS,"denied"
            if path in ("/login","/cadastro","/recuperar") and headers:
                return 400,HEADERS,"denied"
            raise AssertionError("Unknown path or real network attempted")
        with patch.object(canary,"query",side_effect=fake_query):
            canary.main()
        self.assertEqual(1+3+1+2*3+3,len(fake_calls))
        self.assertFalse(any(path.startswith("http") for path,_,_ in fake_calls))


if __name__=="__main__":
    unittest.main(verbosity=2)
