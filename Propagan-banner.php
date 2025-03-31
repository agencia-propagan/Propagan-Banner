<?php
/*
Plugin Name: Propagan Banner
Description: Um plugin moderno para exibir banners responsivos com seleção de imagens pela biblioteca do WordPress.
Version: 2.0
Author: José Lucas Domingues
*/

// Enqueue scripts e styles
function propagan_banner_assets() {
    // CSS
    wp_enqueue_style('propagan-banner-style', plugins_url('css/propagan-banner.css', __FILE__));
    
    // JS
    wp_enqueue_script('propagan-banner-script', plugins_url('js/propagan-banner.js', __FILE__), array('jquery'), '1.0', true);
    
    // Media uploader
    if (is_admin()) {
        wp_enqueue_media();
        wp_enqueue_script('propagan-banner-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'propagan_banner_assets');
add_action('admin_enqueue_scripts', 'propagan_banner_assets');

// Shortcode para exibir os banners
function propagan_banner_shortcode($atts) {
    $atts = shortcode_atts(array(
        'device' => 'desktop',
    ), $atts, 'propagan_banner');

    $banner_data = array();
    for ($i = 1; $i <= 4; $i++) {
        $image_id = get_option("propagan_banner_{$atts['device']}_image{$i}_id", '');
        $image_url = get_option("propagan_banner_{$atts['device']}_image{$i}_url", '');
        $banner_link = get_option("propagan_banner_{$atts['device']}_link{$i}", '');
        
        if (!empty($image_url)) {
            $banner_data[] = array(
                'image_url' => $image_url,
                'link' => $banner_link,
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
            );
        }
    }

    if (empty($banner_data)) {
        return '<div class="propagan-banner-notice">Nenhum banner configurado para este dispositivo.</div>';
    }

    ob_start(); ?>
    <div class="propagan-banner-slider">
        <div class="propagan-slider-container">
            <?php foreach ($banner_data as $index => $banner): ?>
                <div class="propagan-slide">
                    <?php if (!empty($banner['link'])): ?>
                        <a href="<?php echo esc_url($banner['link']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php endif; ?>
                    
                    <img src="<?php echo esc_url($banner['image_url']); ?>" 
                         alt="<?php echo esc_attr($banner['alt']); ?>" 
                         class="propagan-banner-img">
                    
                    <?php if (!empty($banner['link'])): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($banner_data) > 1): ?>
            <div class="propagan-slider-dots">
                <?php foreach ($banner_data as $index => $banner): ?>
                    <button class="propagan-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-slide="<?php echo $index; ?>"
                            aria-label="<?php printf(__('Ir para o slide %d', 'propagan-banner'), $index + 1); ?>">
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="propagan-slider-arrows">
                <button class="propagan-arrow propagan-prev" aria-label="<?php _e('Slide anterior', 'propagan-banner'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
                    </svg>
                </button>
                <button class="propagan-arrow propagan-next" aria-label="<?php _e('Próximo slide', 'propagan-banner'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('propagan_banner', 'propagan_banner_shortcode');

// Página de configurações
function propagan_banner_menu() {
    add_menu_page(
        'Propagan Banner',
        'Propagan Banner',
        'manage_options',
        'propagan-banner',
        'propagan_banner_settings_page',
        'dashicons-slides',
        100
    );
}
add_action('admin_menu', 'propagan_banner_menu');

function propagan_banner_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['propagan_banner_nonce']) && wp_verify_nonce($_POST['propagan_banner_nonce'], 'propagan_banner_save')) {
        $devices = array('desktop', 'tablet', 'mobile');
        
        foreach ($devices as $device) {
            for ($i = 1; $i <= 4; $i++) {
                $image_id_key = "propagan_banner_{$device}_image{$i}_id";
                $image_url_key = "propagan_banner_{$device}_image{$i}_url";
                $link_key = "propagan_banner_{$device}_link{$i}";
                
                if (isset($_POST[$image_id_key])) {
                    update_option($image_id_key, absint($_POST[$image_id_key]));
                }
                
                if (isset($_POST[$image_url_key])) {
                    update_option($image_url_key, esc_url_raw($_POST[$image_url_key]));
                }
                
                if (isset($_POST[$link_key])) {
                    update_option($link_key, esc_url_raw($_POST[$link_key]));
                }
            }
        }
        
        add_settings_error('propagan_banner_messages', 'propagan_banner_message', __('Configurações salvas com sucesso!', 'propagan-banner'), 'updated');
    }
    
    settings_errors('propagan_banner_messages');
    
    ?>
    <div class="wrap propagan-banner-settings">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('propagan_banner_save', 'propagan_banner_nonce'); ?>
            
            <div class="propagan-tabs">
                <?php 
                $devices = array(
                    'desktop' => __('Desktop', 'propagan-banner'),
                    'tablet' => __('Tablet', 'propagan-banner'),
                    'mobile' => __('Mobile', 'propagan-banner')
                );
                
                $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'desktop';
                ?>
                
                <nav class="propagan-tab-nav">
                    <?php foreach ($devices as $device => $label): ?>
                        <a href="?page=propagan-banner&tab=<?php echo $device; ?>" 
                           class="propagan-tab-link <?php echo $active_tab === $device ? 'active' : ''; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                
                <?php foreach ($devices as $device => $label): ?>
                    <div class="propagan-tab-content <?php echo $active_tab === $device ? 'active' : ''; ?>">
                        <h2><?php printf(__('Configurações para %s', 'propagan-banner'), $label); ?></h2>
                        
                        <div class="propagan-banner-grid">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="propagan-banner-item">
                                    <h3><?php printf(__('Banner %d', 'propagan-banner'), $i); ?></h3>
                                    
                                    <?php
                                    $image_id = get_option("propagan_banner_{$device}_image{$i}_id", '');
                                    $image_url = get_option("propagan_banner_{$device}_image{$i}_url", '');
                                    $link = get_option("propagan_banner_{$device}_link{$i}", '');
                                    ?>
                                    
                                    <div class="propagan-image-uploader">
                                        <div class="propagan-image-preview" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
                                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php _e('Pré-visualização', 'propagan-banner'); ?>">
                                            <button type="button" class="propagan-remove-image button">
                                                <?php _e('Remover', 'propagan-banner'); ?>
                                            </button>
                                        </div>
                                        
                                        <input type="hidden" 
                                               name="propagan_banner_<?php echo $device; ?>_image<?php echo $i; ?>_id" 
                                               value="<?php echo esc_attr($image_id); ?>">
                                               
                                        <input type="hidden" 
                                               name="propagan_banner_<?php echo $device; ?>_image<?php echo $i; ?>_url" 
                                               value="<?php echo esc_url($image_url); ?>">
                                               
                                        <button type="button" class="propagan-upload-image button" 
                                                style="<?php echo $image_url ? 'display:none;' : ''; ?>">
                                            <?php _e('Selecionar Imagem', 'propagan-banner'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="propagan-link-field">
                                        <label for="propagan_banner_<?php echo $device; ?>_link<?php echo $i; ?>">
                                            <?php _e('Link:', 'propagan-banner'); ?>
                                        </label>
                                        <input type="url" 
                                               id="propagan_banner_<?php echo $device; ?>_link<?php echo $i; ?>" 
                                               name="propagan_banner_<?php echo $device; ?>_link<?php echo $i; ?>" 
                                               value="<?php echo esc_url($link); ?>" 
                                               placeholder="https://">
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php submit_button(__('Salvar Configurações', 'propagan-banner')); ?>
        </form>
    </div>
    <?php
}

// Criar os arquivos CSS e JS necessários
// Você precisará criar estes arquivos no seu plugin:
// - css/propagan-banner.css
// - js/propagan-banner.js
// - js/admin.js