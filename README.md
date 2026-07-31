# Página do Professor

Plugin WordPress em PHP puro para adicionar campos profissionais ao perfil de usuários e exibir páginas de servidores por shortcode.

## Instalação

1. Copie a pasta `plugin-perfil` para `wp-content/plugins/`.
2. Ative o plugin **Página do Professor** no painel do WordPress.
3. Edite o perfil do usuário e preencha os campos profissionais.
4. Em uma página do WordPress, use o shortcode:

```text
[pagina_do_professor id="20"]
```

Substitua `20` pelo ID do usuário do servidor.

## Shortcode alternativo

```text
[pagina_professor id="20"]
```

O botão **Voltar** usa por padrão `https://fct.ufmt.br/servidores/`. Para substituir:

```text
[pagina_do_professor id="20" voltar_url="https://exemplo.com/servidores/"]
```

## Estrutura

- `plugin-perfil/plugin-perfil.php`: arquivo principal do plugin e campos do perfil.
- `plugin-perfil/pagina professor.php`: shortcodes e template do perfil público.
