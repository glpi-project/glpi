# Template de Notificação - Password Forget

## Template Atualizado

**Template:** Password Forget (ID: 13)  
**Data:** 2026-06-09

## Alteração

### Antes
O link aparecia como texto completo:
```
https://suporte.gruposrm.local/front/lostpassword.php?password_forget_token=...
```

### Depois
Agora exibe um **botão azul estilizado** centralizado:

```html
<div style="text-align: center; font-family: Arial, sans-serif;">
  <h2 style="color: #333;">Recuperação de Senha</h2>
  <p>Olá <strong>##user.realname## ##user.firstname##</strong>,</p>
  <p>##lang.passwordforget.information##</p>
  <div style="margin: 30px 0;">
    <a href="##user.passwordforgeturl##" style="display: inline-block; padding: 15px 30px; 
       background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 5px; 
       font-weight: bold; font-size: 16px;">
      REDEFINIR MINHA SENHA
    </a>
  </div>
  <p style="color: #666; font-size: 12px;">Este link expira em 24 horas.</p>
  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
  <p style="color: #999; font-size: 11px;">
    Suporte de tecnologia Grupo SRM<br>
    Automaticamente gerado por GLPI
  </p>
</div>
```

## Características do Botão

- **Cor:** Azul (#0066cc)
- **Texto:** Branco em negrito
- **Formato:** Retângulo arredondado
- **Posição:** Centralizado
- **Texto:** "REDEFINIR MINHA SENHA"

## Como Aplicar (se necessário no futuro)

Execute o SQL no banco de dados:
```sql
UPDATE glpi_notificationtemplatetranslations 
SET content_html = '<div style="text-align: center; font-family: Arial, sans-serif;">\n<h2 style="color: #333;">Recuperação de Senha</h2>\n<p>Olá <strong>##user.realname## ##user.firstname##</strong>,</p>\n<p>##lang.passwordforget.information##</p>\n<div style="margin: 30px 0;">\n<a href="##user.passwordforgeturl##" style="display: inline-block; padding: 15px 30px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">REDEFINIR MINHA SENHA</a>\n</div>\n<p style="color: #666; font-size: 12px;">Este link expira em 24 horas.</p>\n<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">\n<p style="color: #999; font-size: 11px;">Suporte de tecnologia Grupo SRM<br>Automaticamente gerado por GLPI</p>\n</div>'
WHERE notificationtemplates_id = 13;
```

---
*Documento criado para manter registro da personalização de templates*
