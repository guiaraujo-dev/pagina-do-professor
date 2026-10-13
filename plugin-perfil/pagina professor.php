<?php

defined( 'ABSPATH' ) || exit;

function fct_professor_obter_usuarios() {
    $usuarios = get_users(array(
        'fields'   => array('ID', 'display_name', 'user_login', 'user_nicename', 'user_email'),
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'number'   => -1,
        'exclude'  => array(1),
    ));

    $lista = array();
    foreach ($usuarios as $user) {
        $lista[] = array(
            'id'    => (int) $user->ID,
            'nome'  => $user->display_name ? $user->display_name : $user->user_login,
            'login' => $user->user_login,
            'slug'  => $user->user_nicename,
            'email' => $user->user_email,
            'link'  => home_url('/servidores/' . $user->user_nicename . '/'),
        );
    }

    return $lista;
}

function fct_professor_resolver_usuario($id = 0, $slug = '', $login = '') {
    if (!empty($id)) {
        return get_userdata((int) $id);
    }

    if (!empty($slug)) {
        return get_user_by('slug', sanitize_title($slug));
    }

    if (!empty($login)) {
        return get_user_by('login', sanitize_user($login));
    }

    $slug_atual = get_query_var('fct_professor_slug');
    if (!empty($slug_atual)) {
        return get_user_by('slug', sanitize_title($slug_atual));
    }

    return false;
}

function fct_professor_carregar_dados_do_perfil($user_id, $fallback = array()) {
    $dados = is_array($fallback) ? $fallback : array();
    $user  = get_userdata($user_id);

    if ($user) {
        if (empty($dados['nome'])) {
            $dados['nome'] = $user->display_name;
        }

        if (empty($dados['email'])) {
            $dados['email'] = $user->user_email;
        }

        if (empty($dados['foto'])) {
            $avatar_url = get_avatar_url($user_id, array('size' => 600));
            if ($avatar_url) {
                $dados['foto'] = $avatar_url;
            }
        }
    }

    $cargo = get_user_meta($user_id, 'plugin_perfil_cargo_categoria', true);
    if (!empty($cargo)) {
        $dados['cargo'] = $cargo;
    }

    $titulacao = get_user_meta($user_id, 'plugin_perfil_titulacao_principal', true);
    if (!empty($titulacao)) {
        $dados['titulacao_resumo'] = $titulacao;
    }

    $lattes = get_user_meta($user_id, 'plugin_perfil_curriculo_lattes', true);
    if (!empty($lattes)) {
        $dados['lattes'] = $lattes;
    }

    $campus = get_user_meta($user_id, 'plugin_perfil_vinculo_institucional', true);
    if (!empty($campus)) {
        $dados['campus'] = $campus;
    }

    $sobre = get_user_meta($user_id, 'plugin_perfil_sobre', true);
    if (!empty($sobre)) {
        $dados['sobre'] = $sobre;
    }

    $atuacao = get_user_meta($user_id, 'plugin_perfil_atuacao', true);
    if (!empty($atuacao)) {
        $dados['atuacao'] = $atuacao;
    }

    $historico = get_user_meta($user_id, 'plugin_perfil_historico_formacao', true);
    if (is_array($historico) && !empty($historico)) {
        $dados['formacao'] = array();
        foreach ($historico as $item) {
            $dados['formacao'][] = array(
                'titulo'      => isset($item['titulo']) ? $item['titulo'] : '',
                'instituicao' => isset($item['instituicao']) ? $item['instituicao'] : '',
                'ano'         => isset($item['ano']) ? $item['ano'] : '',
                'detalhe'     => '',
            );
        }
    }

    return $dados;
}

function fct_professores_dados() {
    $professores = array();
    $usuarios = fct_professor_obter_usuarios();

    foreach ($usuarios as $usuario) {
        $professores[$usuario['id']] = array(
            'nome'            => $usuario['nome'],
            'cargo'           => '',
            'foto'            => '',
            'email'           => $usuario['email'],
            'lattes'          => '',
            'campus'          => '',
            'titulacao_resumo'=> '',
            'sobre'           => '',
            'formacao'        => array(),
            'atuacao'         => '',
        );
    }

    foreach ($professores as $id => $dados) {
        $professores[$id] = fct_professor_carregar_dados_do_perfil($id, $dados);
    }
 
    return apply_filters('fct_professores_dados', $professores);
}
 
$pagina_do_professor_shortcode = function ($atts) {
 
    $a = shortcode_atts(array(
        'id'         => 0,
        'slug'       => '',
        'login'      => '',
        'voltar_url' => '', // opcional: URL da listagem de professores, usada só se não houver histórico de navegação
    ), $atts);
    $id = intval($a['id']);
    $slug = sanitize_title($a['slug']);
    $login = sanitize_user($a['login']);
    $voltar_url = ! empty( $a['voltar_url'] ) ? $a['voltar_url'] : 'https://fct.ufmt.br/servidores/';
 
    $usuario = fct_professor_resolver_usuario($id, $slug, $login);
    if (!$usuario) {
        return fct_professor_render_lista_usuarios($voltar_url);
    }

    $id = (int) $usuario->ID;
    $professores = fct_professores_dados();
    if (!isset($professores[$id])) {
        return "<div style='padding:30px;color:#64748b;'>Perfil de professor não encontrado.</div>";
    }
    $p = $professores[$id];
 
    // Conta publicações do professor para o badge da aba (sem exibir a lista aqui)
    $qtd_publicacoes = count_user_posts($id, 'post');
    $qtd_formacao     = count($p['formacao']);
 
    ob_start();
    ?>
 
    <style>
    .fp-wrap{
        --fp-navy:#071d41;
        --fp-blue:#1e56d9;
        --fp-gold:#e8a30a;
        --fp-bg:#f4f6fb;
        --fp-text:#334155;
        --fp-border:#e2e8f0;
        font-family:"Montserrat", sans-serif;
        color:var(--fp-text);
        max-width:1180px;
        margin:0 auto;
        padding:10px 20px 50px;
    }
    /* barra Voltar / Compartilhar */
    .fp-toolbar{
        display:flex; align-items:center; justify-content:space-between;
        padding:18px 0 6px; flex-wrap:wrap; gap:10px;
    }
    .fp-tb-btn{
        display:inline-flex; align-items:center; gap:7px;
        background:none; border:none; cursor:pointer; font-family:inherit;
        font-size:14.5px; font-weight:700; color:var(--fp-navy);
        padding:6px 4px; text-decoration:none; position:relative;
    }
    .fp-tb-btn:hover{ color:var(--fp-blue); }
    .fp-tb-btn:focus-visible{ outline:2px solid var(--fp-blue); outline-offset:3px; border-radius:4px; }
    .fp-tb-btn svg{ flex-shrink:0; }
    .fp-share-toast{
        position:absolute; top:100%; right:0; margin-top:8px;
        background:var(--fp-navy); color:#fff; font-size:12.5px; font-weight:600;
        padding:6px 12px; border-radius:6px; white-space:nowrap;
        opacity:0; pointer-events:none; transform:translateY(-4px);
        transition:opacity .18s ease, transform .18s ease;
    }
    .fp-share-toast.is-visible{ opacity:1; transform:translateY(0); }
 
    .fp-hero{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:16px;
        border-bottom:1px solid var(--fp-border);
        padding-bottom:22px;
        margin-bottom:28px;
    }
    .fp-hero h1{
        font-family:"Fraunces","Georgia",serif;
        font-weight:600;
        font-size:32px;
        color:var(--fp-navy);
        margin:0 0 10px;
        line-height:1.15;
    }
    .fp-cargo{
        font-size:14px;
        font-weight:700;
        color:var(--fp-blue);
        text-transform:uppercase;
        letter-spacing:.06em;
        margin-bottom:14px;
    }
    /* faixa de credenciais — elemento de assinatura */
    .fp-chips{ display:flex; flex-wrap:wrap; gap:8px; }
    .fp-chip{
        background:var(--fp-bg);
        border:1px solid var(--fp-border);
        color:var(--fp-navy);
        font-size:12.5px;
        font-weight:700;
        padding:6px 12px;
        border-radius:999px;
        white-space:nowrap;
    }
    .fp-chip.fp-chip-gold{ background:#fff6e2; border-color:#f3d38a; color:#7a5400; }
 
    .fp-body{ display:flex; gap:32px; align-items:flex-start; }
 
    /* --- sidebar --- */
    .fp-side{ width:280px; flex-shrink:0; position:sticky; top:100px; }
    .fp-photo{
        width:100%; aspect-ratio:1/1; border-radius:14px; overflow:hidden;
        background:var(--fp-navy); margin-bottom:16px; box-shadow:0 8px 24px rgba(7,29,65,.12);
    }
    .fp-photo img{ width:100%; height:100%; object-fit:cover; display:block; }
    .fp-contact-card{
        background:#fff; border:1px solid var(--fp-border); border-radius:12px; padding:18px;
    }
    .fp-contact-card h3{
        font-size:13px; text-transform:uppercase; letter-spacing:.06em;
        color:#94a3b8; margin:0 0 12px; font-weight:800;
    }
    .fp-contact-row{
        display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600;
        color:var(--fp-navy); text-decoration:none; margin-bottom:10px;
    }
    .fp-contact-row:last-child{ margin-bottom:0; }
    .fp-contact-row svg{ flex-shrink:0; }
    .fp-campus-note{
        margin-top:16px; background:var(--fp-navy); color:#fff; font-size:13px;
        line-height:1.5; padding:14px 16px; border-radius:10px;
    }
 
    /* --- tabs --- */
    .fp-main{ flex:1; min-width:0; }
    .fp-tabs{
        display:flex; gap:4px; border-bottom:2px solid var(--fp-border);
        margin-bottom:26px; overflow-x:auto;
    }
    .fp-tab-btn{
        background:none; border:none; cursor:pointer; font-family:inherit;
        font-size:15px; font-weight:700; color:#94a3b8; padding:12px 18px;
        border-bottom:3px solid transparent; margin-bottom:-2px; white-space:nowrap;
        transition:color .15s ease, border-color .15s ease;
    }
    .fp-tab-btn:hover{ color:var(--fp-blue); }
    .fp-tab-btn.is-active{ color:var(--fp-navy); border-bottom-color:var(--fp-gold); }
    .fp-tab-btn:focus-visible{ outline:2px solid var(--fp-blue); outline-offset:2px; }
    .fp-tab-count{
        display:inline-block; background:var(--fp-bg); color:var(--fp-navy);
        font-size:11px; font-weight:800; border-radius:999px; padding:1px 7px; margin-left:6px;
    }
    .fp-tab-btn.is-active .fp-tab-count{ background:var(--fp-gold); color:#fff; }
 
    .fp-panel{ display:none; animation:fpFade .25s ease; }
    .fp-panel.is-active{ display:block; }
    @keyframes fpFade{ from{opacity:0; transform:translateY(4px);} to{opacity:1; transform:translateY(0);} }
 
    .fp-section-title{
        font-family:"Fraunces","Georgia",serif; font-weight:600; font-size:21px;
        color:var(--fp-navy); margin:0 0 14px;
    }
    .fp-sobre-text{ font-size:15.5px; line-height:1.75; max-width:70ch; }
 
    /* linha do tempo de formação */
    .fp-timeline{ list-style:none; margin:0; padding:0; border-left:2px solid var(--fp-border); }
    .fp-timeline li{ position:relative; padding:0 0 22px 24px; }
    .fp-timeline li:last-child{ padding-bottom:0; }
    .fp-timeline li::before{
        content:""; position:absolute; left:-7px; top:4px; width:12px; height:12px;
        border-radius:50%; background:#fff; border:3px solid var(--fp-blue);
    }
    .fp-tl-titulo{ font-weight:800; color:var(--fp-navy); font-size:15.5px; }
    .fp-tl-ano{ color:var(--fp-gold); font-weight:800; font-size:13px; margin-left:8px; }
    .fp-tl-inst{ font-size:14px; margin-top:2px; }
    .fp-tl-detalhe{ font-size:13.5px; color:#64748b; font-style:italic; margin-top:4px; }
 
    .fp-atuacao-box{
        border-left:4px solid var(--fp-gold); background:var(--fp-bg);
        padding:16px 20px; font-weight:600; color:var(--fp-navy); border-radius:0 8px 8px 0;
    }
 
    @media (max-width:900px){
        .fp-body{ flex-direction:column; }
        .fp-side{ position:static; width:100%; display:flex; gap:20px; align-items:flex-start; }
        .fp-photo{ width:120px; flex-shrink:0; margin-bottom:0; }
        .fp-contact-card{ flex:1; }
        .fp-campus-note{ margin-top:0; }
    }
    @media (max-width:600px){
        .fp-side{ flex-direction:column; }
        .fp-photo{ width:100%; }
        .fp-hero h1{ font-size:26px; }
    }
    </style>
 
    <div class="fp-wrap">
 
        <div class="fp-toolbar">
            <button class="fp-tb-btn fp-voltar-btn" type="button" data-fallback="<?php echo esc_url($voltar_url); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>
                Voltar
            </button>
            <button class="fp-tb-btn fp-compartilhar-btn" type="button" data-title="<?php echo esc_attr($p['nome']); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                Compartilhar
                <span class="fp-share-toast">Link copiado!</span>
            </button>
        </div>
 
        <div class="fp-hero">
            <div>
                <div class="fp-cargo"><?php echo esc_html($p['cargo']); ?></div>
                <h1><?php echo esc_html($p['nome']); ?></h1>
                <div class="fp-chips">
                    <span class="fp-chip fp-chip-gold"><?php echo esc_html($p['titulacao_resumo']); ?></span>
                    <span class="fp-chip"><?php echo esc_html($qtd_formacao); ?> formações acadêmicas</span>
                    <span class="fp-chip"><?php echo esc_html($qtd_publicacoes); ?> publicações</span>
                </div>
            </div>
        </div>
 
        <div class="fp-body">
 
            <aside class="fp-side">
                <div class="fp-photo">
                    <img src="<?php echo esc_url($p['foto']); ?>" alt="Foto de <?php echo esc_attr($p['nome']); ?>">
                </div>
                <div class="fp-contact-card">
                    <h3>Contato</h3>
                    <a class="fp-contact-row" href="mailto:<?php echo esc_attr($p['email']); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1e56d9" stroke-width="2"><path d="M3 5h18v14H3z"/><path d="M3 6l9 7 9-7"/></svg>
                        <?php echo esc_html($p['email']); ?>
                    </a>
                    <a class="fp-contact-row" href="<?php echo esc_url($p['lattes']); ?>" target="_blank" rel="noopener">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1e56d9" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M21 14v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6"/></svg>
                        Currículo Lattes
                    </a>
                </div>
                <div class="fp-campus-note"><?php echo esc_html($p['campus']); ?></div>
            </aside>
 
            <div class="fp-main">
                <div class="fp-tabs" role="tablist" aria-label="Seções do perfil">
                    <button class="fp-tab-btn is-active" role="tab" aria-selected="true" data-tab="sobre">Sobre</button>
                    <button class="fp-tab-btn" role="tab" aria-selected="false" data-tab="formacao">Formação<span class="fp-tab-count"><?php echo esc_html($qtd_formacao); ?></span></button>
                    <button class="fp-tab-btn" role="tab" aria-selected="false" data-tab="atuacao">Atuação</button>
                </div>
 
                <div class="fp-panel is-active" data-panel="sobre">
                    <h2 class="fp-section-title">Sobre mim</h2>
                    <div class="fp-sobre-text"><?php echo wpautop(wp_kses_post($p['sobre'])); ?></div>
                </div>
 
                <div class="fp-panel" data-panel="formacao">
                    <h2 class="fp-section-title">Formação Acadêmica</h2>
                    <ul class="fp-timeline">
                        <?php foreach ($p['formacao'] as $f): ?>
                            <li>
                                <span class="fp-tl-titulo"><?php echo esc_html($f['titulo']); ?></span>
                                <?php if (!empty($f['ano'])): ?><span class="fp-tl-ano"><?php echo esc_html($f['ano']); ?></span><?php endif; ?>
                                <div class="fp-tl-inst"><?php echo esc_html($f['instituicao']); ?></div>
                                <?php if (!empty($f['detalhe'])): ?><div class="fp-tl-detalhe"><?php echo esc_html($f['detalhe']); ?></div><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
 
                <div class="fp-panel" data-panel="atuacao">
                    <h2 class="fp-section-title">Atuação Profissional</h2>
                    <div class="fp-atuacao-box"><?php echo wpautop(wp_kses_post($p['atuacao'])); ?></div>
                </div>
 
            </div>
        </div>
    </div>
 
    <script>
    (function(){
        var wraps = document.querySelectorAll('.fp-wrap');
        wraps.forEach(function(wrap){
            var btns = wrap.querySelectorAll('.fp-tab-btn');
            var panels = wrap.querySelectorAll('.fp-panel');
 
            function activate(tab){
                btns.forEach(function(b){
                    var active = b.dataset.tab === tab;
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(function(p){
                    p.classList.toggle('is-active', p.dataset.panel === tab);
                });
            }
 
            btns.forEach(function(btn, i){
                btn.addEventListener('click', function(){
                    activate(btn.dataset.tab);
                    history.replaceState(null, '', '#' + btn.dataset.tab);
                });
                btn.addEventListener('keydown', function(e){
                    if (e.key === 'ArrowRight') btns[(i+1) % btns.length].focus();
                    if (e.key === 'ArrowLeft') btns[(i-1+btns.length) % btns.length].focus();
                });
            });
 
            var hash = window.location.hash.replace('#', '');
            if (hash && wrap.querySelector('.fp-tab-btn[data-tab="' + hash + '"]')) {
                activate(hash);
            }
 
            // --- Voltar ---
            var voltarBtn = wrap.querySelector('.fp-voltar-btn');
            if (voltarBtn) {
                voltarBtn.addEventListener('click', function(){
                    var fallback = voltarBtn.dataset.fallback;
                    // A página de servidores é o destino padrão do botão.
                    if (fallback) {
                        window.location.href = fallback;
                    }
                });
            }
 
            // --- Compartilhar ---
            var shareBtn = wrap.querySelector('.fp-compartilhar-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', function(){
                    var url = window.location.href;
                    var title = shareBtn.dataset.title || document.title;
 
                    if (navigator.share) {
                        // Celular / navegadores com suporte nativo: abre o menu de compartilhamento do sistema
                        navigator.share({ title: title, url: url }).catch(function(){});
                    } else {
                        // Computador: copia o link e mostra um aviso
                        navigator.clipboard.writeText(url).then(function(){
                            var toast = shareBtn.querySelector('.fp-share-toast');
                            if (toast) {
                                toast.classList.add('is-visible');
                                setTimeout(function(){ toast.classList.remove('is-visible'); }, 2000);
                            }
                        });
                    }
                });
            }
        });
    })();
    </script>
 
    <?php
    $perfil_html = ob_get_clean();

    $publicacoes_html = do_shortcode(
        '[fct_posts_professor_teste id="' . absint( $id ) . '"]'
    );

    return $perfil_html . '<section class="fp-publicacoes-integradas">' . $publicacoes_html . '</section>';
};

add_shortcode('pagina_do_professor', $pagina_do_professor_shortcode);
add_shortcode('pagina_professor', $pagina_do_professor_shortcode);

function fct_professor_render_lista_usuarios($voltar_url = '') {
    $usuarios = fct_professor_obter_usuarios();
    ob_start();
    ?>
    <style>
    .fp-lista-wrap{ font-family:"Montserrat", sans-serif; color:#334155; max-width:1180px; margin:0 auto; padding:20px 0 40px; }
    .fp-lista-title{ font-size:24px; font-weight:700; color:#071d41; margin:0 0 16px; }
    .fp-lista-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px; }
    .fp-lista-card{ display:block; text-decoration:none; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; box-shadow:0 4px 12px rgba(7,29,65,.05); }
    .fp-lista-card:hover{ border-color:#1e56d9; transform:translateY(-2px); transition:all .15s ease; }
    .fp-lista-card h3{ margin:0 0 8px; color:#071d41; font-size:18px; }
    .fp-lista-card p{ margin:0 0 8px; color:#64748b; font-size:14px; }
    .fp-lista-card .fp-lista-meta{ font-size:12px; font-weight:700; color:#1e56d9; }
    </style>
    <div class="fp-lista-wrap">
        <h2 class="fp-lista-title">Servidores</h2>
        <div class="fp-lista-grid">
            <?php foreach ($usuarios as $usuario): ?>
                <?php
                $dados = fct_professor_carregar_dados_do_perfil($usuario['id']);
                $cargo = !empty($dados['cargo']) ? $dados['cargo'] : 'Servidor';
                ?>
                <a class="fp-lista-card" href="<?php echo esc_url($usuario['link']); ?>">
                    <h3><?php echo esc_html($usuario['nome']); ?></h3>
                    <p><?php echo esc_html($cargo); ?></p>
                    <p><?php echo esc_html($usuario['email']); ?></p>
                    <div class="fp-lista-meta">Ver perfil completo</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function fct_professor_registrar_rewrite() {
    add_rewrite_tag('%fct_professor_slug%', '([^&]+)');
    add_rewrite_rule('^servidores/([^/]+)/?$', 'index.php?fct_professor_slug=$matches[1]', 'top');

    if (!get_option('fct_professor_rewrite_flushed')) {
        flush_rewrite_rules();
        update_option('fct_professor_rewrite_flushed', 1);
    }
}
add_action('init', 'fct_professor_registrar_rewrite');

function fct_pub_categorias_do_autor($author_id) {
    $ids = get_posts(array(
        'post_type'      => 'post',
        'author'         => $author_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));
 
    $categorias = array();
    foreach ($ids as $post_id) {
        foreach (get_the_category($post_id) as $cat) {
            $categorias[$cat->term_id] = $cat;
        }
    }
    uasort($categorias, function ($a, $b) { return strcasecmp($a->name, $b->name); });
    return $categorias;
}
 
add_shortcode('fct_posts_professor_teste', function ($atts) {
 
    $a = shortcode_atts(array(
        'id' => 20, // Padrão é o ID 20 (Adriano)
    ), $atts);
    $author_id = intval($a['id']);
 
    // Cada professor tem seu próprio "namespace" de filtros na URL (pub20_cat, pub20_s...)
    // para o caso de duas listas de professores diferentes aparecerem na mesma página.
    $ns = 'pub' . $author_id;
 
    $filtro_cat = isset($_GET[$ns . '_cat']) ? sanitize_title(wp_unslash($_GET[$ns . '_cat'])) : '';
    $filtro_s   = isset($_GET[$ns . '_s']) ? sanitize_text_field(wp_unslash($_GET[$ns . '_s'])) : '';
    $paged      = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $tem_filtro = ($filtro_cat !== '' || $filtro_s !== '');
 
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 6,
        'author'         => $author_id,
        'paged'          => $paged,
    );
    if ($filtro_cat !== '') $args['category_name'] = $filtro_cat;
    if ($filtro_s   !== '') $args['s']              = $filtro_s;
 
    $query      = new WP_Query($args);
    $categorias = fct_pub_categorias_do_autor($author_id);
 
    // URL base para o form (sem os parâmetros de filtro antigos, sem reprocessar querystring)
    $base_url = strtok(add_query_arg(array()), '?');
 
    ob_start();
    ?>
    <style>
    .pubx-section{ width:100%; font-family:"Montserrat", sans-serif; margin:0; scroll-margin-top:100px; }
    .pubx-header{
        font-size:24px; font-weight:700; color:#071d41; margin-bottom:20px;
        padding-left:15px; border-left:6px solid #173A87; line-height:1;
    }
 
    /* --- barra de filtros --- */
    .pubx-filtros{
        display:flex; flex-wrap:wrap; align-items:center; gap:8px;
        background:#f4f6fb; border:1px solid #e2e8f0; border-radius:10px;
        padding:10px 14px; margin-bottom:16px;
    }
    .pubx-filtros select, .pubx-filtros input[type="text"]{
        font-family:inherit; font-size:13px; color:#071d41; font-weight:600;
        border:1px solid #dde3ee; border-radius:7px; padding:7px 10px;
        background:#fff; min-width:150px;
    }
    .pubx-filtros input[type="text"]{ min-width:180px; flex:1; }
    .pubx-filtros button{
        font-family:inherit; font-size:13px; font-weight:700; cursor:pointer;
        background:#071d41; color:#fff; border:none; border-radius:7px; padding:7px 16px;
        transition:background .15s ease;
    }
    .pubx-filtros button:hover{ background:#1e56d9; }
    .pubx-limpar{
        font-size:12.5px; font-weight:700; color:#94a3b8; text-decoration:none;
        padding:7px 4px;
    }
    .pubx-limpar:hover{ color:#071d41; text-decoration:underline; }
 
    /* --- grade de cards --- */
    .pubx-grid{ display:grid; grid-template-columns:repeat(6, 1fr); gap:12px; width:100%; }
    .pubx-item{
        position:relative; border-radius:10px; overflow:hidden; height:150px;
        display:block; background-color:#071d41; text-decoration:none !important;
        box-shadow:0 3px 10px rgba(7,29,65,.08);
    }
    .pubx-bg{
        position:absolute; inset:0; background-size:cover; background-position:center;
        z-index:1; transition:transform .5s ease, filter .5s ease;
    }
    .pubx-item:hover .pubx-bg{ transform:scale(1.08); filter:brightness(1.08); }
    .pubx-no-thumb .pubx-bg{ background:linear-gradient(135deg, #071d41 0%, #1e56d9 100%) !important; }
    .pubx-overlay{
        position:absolute; inset:0; display:flex; flex-direction:column;
        justify-content:space-between; padding:10px; z-index:2;
    }
    .pubx-has-thumb .pubx-overlay{
        background:linear-gradient(0deg, #071d41 0%, rgba(7,29,65,.15) 55%, transparent 100%) !important;
    }
    .pubx-top-row{ display:flex; justify-content:space-between; align-items:flex-start; gap:6px; }
    .pubx-cat-tag{
        background:#e8a30a !important; color:#071d41 !important; font-size:9px; font-weight:800;
        padding:3px 7px; border-radius:4px; text-transform:uppercase; width:fit-content;
    }
    .pubx-year{
        background:rgba(255,255,255,.16); color:#fff; font-size:10px; font-weight:800;
        padding:3px 7px; border-radius:4px; backdrop-filter:blur(2px); flex-shrink:0;
    }
    .pubx-title{
        color:#fff !important; margin:0 !important; font-weight:700; font-size:13px;
        line-height:1.25; text-shadow:0 2px 8px rgba(0,0,0,.5);
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
 
    /* --- paginação --- */
    .pubx-pagination{ display:flex; justify-content:center; flex-wrap:wrap; gap:6px; margin-top:18px; }
    .pubx-pagination a, .pubx-pagination span{
        padding:7px 14px; border-radius:7px; background:#fff; border:1px solid #e2e8f0;
        color:#071d41; text-decoration:none; font-weight:700; font-size:13px;
    }
    .pubx-pagination a:hover{ border-color:#1e56d9; color:#1e56d9; }
    .pubx-pagination .current{ background:#071d41 !important; color:#fff !important; border-color:#071d41; }
 
    .pubx-vazio{
        border:2px dashed #e2e8f0; padding:28px; border-radius:12px;
        text-align:center; color:#64748b; font-size:14px;
    }
 
    @media (max-width:1200px){ .pubx-grid{ grid-template-columns:repeat(4, 1fr); } }
    @media (max-width:1024px){ .pubx-grid{ grid-template-columns:repeat(3, 1fr); } }
    @media (max-width:600px){
        .pubx-grid{ grid-template-columns:repeat(2, 1fr); }
        .pubx-item{ height:130px; }
        .pubx-filtros{ flex-direction:column; align-items:stretch; }
        .pubx-filtros select, .pubx-filtros input[type="text"], .pubx-filtros button{ width:100%; }
    }
    </style>
 
    <div id="publicacoes-<?php echo esc_attr($author_id); ?>" class="pubx-section">
        <h2 class="pubx-header">Publicações do professor</h2>
 
        <?php if (!empty($categorias) || $query->have_posts() || $tem_filtro): ?>
        <form class="pubx-filtros" method="get" action="<?php echo esc_url($base_url); ?>">
            <?php if (!empty($categorias)): ?>
                <select name="<?php echo esc_attr($ns); ?>_cat">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($filtro_cat, $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
 
            <input type="text" name="<?php echo esc_attr($ns); ?>_s" placeholder="Buscar por título..." value="<?php echo esc_attr($filtro_s); ?>">
 
            <button type="submit">Filtrar</button>
 
            <?php if ($tem_filtro): ?>
                <a class="pubx-limpar" href="<?php echo esc_url($base_url); ?>#publicacoes-<?php echo esc_attr($author_id); ?>">Limpar filtros</a>
            <?php endif; ?>
        </form>
        <?php endif; ?>
 
        <?php if (!$query->have_posts()): ?>
 
            <?php if ($tem_filtro): ?>
                <div class="pubx-vazio"><p>Nenhuma publicação encontrada para esse filtro. Tente outra categoria ou termo de busca.</p></div>
            <?php else: ?>
                <div class="pubx-vazio"><p>Ainda não foram encontradas publicações para este perfil.</p></div>
            <?php endif; ?>
 
        <?php else: ?>
 
            <div class="pubx-grid">
                <?php while ($query->have_posts()): $query->the_post();
                    $thumb_url   = get_the_post_thumbnail_url(get_the_ID(), 'large');
                    $thumb_class = $thumb_url ? 'pubx-has-thumb' : 'pubx-no-thumb';
                    $bg_style    = $thumb_url ? "style='background-image:url($thumb_url);'" : '';
                    $category    = get_the_category();
                    $cat_name    = !empty($category) ? $category[0]->name : 'Geral';
                    $ano         = get_the_date('Y');
                ?>
                    <a href="<?php the_permalink(); ?>" class="pubx-item <?php echo esc_attr($thumb_class); ?>">
                        <div class="pubx-bg" <?php echo $bg_style; ?>></div>
                        <div class="pubx-overlay">
                            <div class="pubx-top-row">
                                <span class="pubx-cat-tag"><?php echo esc_html($cat_name); ?></span>
                                <span class="pubx-year"><?php echo esc_html($ano); ?></span>
                            </div>
                            <h2 class="pubx-title"><?php the_title(); ?></h2>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
 
            <div class="pubx-pagination">
                <?php echo paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%') . '#publicacoes-' . $author_id,
                    'total'     => $query->max_num_pages,
                    'current'   => $paged,
                    'format'    => '?paged=%#%',
                    'prev_text' => '« Anterior',
                    'next_text' => 'Próximo »',
                )); ?>
            </div>
 
        <?php endif; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
});