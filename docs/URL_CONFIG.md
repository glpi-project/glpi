# Configuração de URL do GLPI - Grupo SRM

## URL Base Configurada

**URL de Acesso:** `http://suporte.gruposrm.local`

## Configuração no Banco de Dados

A URL base está configurada na tabela `glpi_configs`:

```sql
UPDATE glpi_configs SET value = 'http://suporte.gruposrm.local' WHERE name = 'url_base';
```

## Importante

Esta configuração afeta todos os links gerados nos emails do sistema:
- Recuperação de senha
- Notificações de tickets
- Links para documentos
- URLs de acompanhamento

## Alteração Histórica

- **Data:** 2026-06-09
- **Anterior:** `https://suporte`
- **Atual:** `http://suporte.gruposrm.local`
- **Motivo:** Corrigir links nos emails para apontar para o endereço correto de acesso interno

## Como Aplicar

Execute no banco de dados MySQL:
```sql
USE glpi;
UPDATE glpi_configs SET value = 'http://suporte.gruposrm.local' WHERE name = 'url_base';
```

Ou via interface web:
1. Acesse: **Setup > General > General setup**
2. Campo: **URL of the application**
3. Valor: `http://suporte.gruposrm.local`

---
*Documento criado automaticamente para manter registro da configuração de produção*
