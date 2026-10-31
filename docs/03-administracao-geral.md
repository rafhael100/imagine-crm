# 03 — Administração Geral da Imagine

## Permissões e acesso
- Permissões administrativas configuráveis por módulo e ação.
- Ações críticas exigem confirmação pelo **mesmo administrador autorizado**, sem obrigar aprovação de um segundo administrador.
- Justificativa e auditoria para operações críticas conforme sua natureza.
- Administradores gerais **não possuem acesso irrestrito automático** aos dados de negócio dos clientes; acesso excepcional requer autorização e auditoria.

## Gestão das empresas
- Suspensão manual por autorizado com justificativa e auditoria; não exclui dados.
- Reativação manual por autorizado com justificativa e auditoria; não prorroga trial e não perdoa dívida.
- Desativação administrativa preserva dados. Exclusão definitiva exige confirmação reforçada, justificativa, auditoria e observância da LGPD/obrigações legais.

## Segurança de administradores gerais
- MFA obrigatório, preferencialmente autenticador ou passkey.
- Sessão expira após **30 minutos de inatividade**.
- Usuário pode administrar suas sessões; superadministrador pode revogar sessões.
- Na troca de senha, oferecer revogação de demais sessões.
- Auditoria de login: dispositivo, IP e tentativas malsucedidas.
- Códigos de recuperação de uso único; perda simultânea de dispositivo/códigos requer verificação de identidade por superadministrador e auditoria, **não apenas e-mail**.
- Auditoria administrativa central protegida de edição por administradores ordinários, sem registro de segredos; retenção padrão de **5 anos**, sujeita a LGPD/lei.

## Administração do produto
- Painel de configurações globais com permissões; regras fundamentais do negócio (14 dias, um trial por CNPJ) protegidas.
- Negociação de valores/assentos por empresa auditada.
- Painel de saúde operacional e alertas, sem exibir dados confidenciais de clientes.
- Acesso de suporte excepcional, auditado.
