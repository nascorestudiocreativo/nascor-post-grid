<?php
/**
 * Plugin Name: Nascor Post Grid
 * Plugin URI:  https://nascor.ar
 * Description: Cuadrícula de Entradas Dinámica con Buscador, Filtros y diseño personalizable.
 * Version:     1.1.5
 * Author:      nascor
 * Text Domain: nascor-post-grid
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Nascor_Post_Grid_Plugin' ) ) {

    class Nascor_Post_Grid_Plugin {

        public function __construct() {
            // Registrar shortcode
            add_shortcode( 'nascor_posts', [ $this, 'render_shortcode' ] );
            
            // Endpoints para AJAX
            add_action( 'wp_ajax_nascor_fetch_posts', [ $this, 'ajax_fetch_posts' ] );
            add_action( 'wp_ajax_nopriv_nascor_fetch_posts', [ $this, 'ajax_fetch_posts' ] );

            // Panel de Administrador
            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        }

        /**
         * ==========================================
         * 1. CONFIGURACIÓN DEL PANEL DE ADMINISTRADOR
         * ==========================================
         */
        public function add_admin_menu() {
            add_menu_page(
                'Nascor Post Grid',
                'Nascor Grid',
                'manage_options',
                'nascor-post-grid',
                [ $this, 'admin_page_html' ],
                'dashicons-grid-view',
                20
            );
        }

        public function register_settings() {
            register_setting( 'nascor_pg_settings', 'npg_bg_top' );
            register_setting( 'nascor_pg_settings', 'npg_bg_bottom' );
            register_setting( 'nascor_pg_settings', 'npg_text_color' );
            register_setting( 'nascor_pg_settings', 'npg_btn_bg' );
            register_setting( 'nascor_pg_settings', 'npg_btn_text' );
            register_setting( 'nascor_pg_settings', 'npg_border_radius' );
        }

        public function admin_page_html() {
            if ( ! current_user_can( 'manage_options' ) ) return;
            
            // Valores por defecto
            $bg_top = get_option( 'npg_bg_top', '#162454' );
            $bg_bottom = get_option( 'npg_bg_bottom', '#0d1236' );
            $text_color = get_option( 'npg_text_color', '#ffffff' );
            $btn_bg = get_option( 'npg_btn_bg', '#ffffff' );
            $btn_text = get_option( 'npg_btn_text', '#0d1236' );
            $border_radius = get_option( 'npg_border_radius', '16' );
            ?>
            <div class="wrap">
                <h1>Configuración de Nascor Post Grid</h1>
                <p>Bienvenido al panel de configuración. Modifica los estilos de tu cuadrícula y revisa cómo queda en la vista previa abajo.</p>
                
                <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #2271b1; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h3>📌 Instrucciones de Integración</h3>
                    <p>Para mostrar esta cuadrícula de entradas en cualquier página, entrada o widget, copia y pega el siguiente shortcode:</p>
                    <p><code style="font-size: 16px; padding: 5px 10px; background: #f0f0f1;">[nascor_posts]</code></p>
                </div>

                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                        <form method="post" action="options.php">
                            <?php settings_fields( 'nascor_pg_settings' ); ?>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row">Color de Fondo Superior (Gradiente)</th>
                                    <td><input type="color" name="npg_bg_top" value="<?php echo esc_attr( $bg_top ); ?>" /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Color de Fondo Inferior (Gradiente)</th>
                                    <td><input type="color" name="npg_bg_bottom" value="<?php echo esc_attr( $bg_bottom ); ?>" /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Color del Texto Principal</th>
                                    <td><input type="color" name="npg_text_color" value="<?php echo esc_attr( $text_color ); ?>" /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Color Fondo del Botón</th>
                                    <td><input type="color" name="npg_btn_bg" value="<?php echo esc_attr( $btn_bg ); ?>" /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Color Texto del Botón</th>
                                    <td><input type="color" name="npg_btn_text" value="<?php echo esc_attr( $btn_text ); ?>" /></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Bordes Redondeados (Tarjetas) en px</th>
                                    <td><input type="number" name="npg_border_radius" value="<?php echo esc_attr( $border_radius ); ?>" /> px</td>
                                </tr>
                            </table>
                            <?php submit_button('Guardar Estilos y Actualizar Vista Previa'); ?>
                        </form>
                    </div>

                    <div style="flex: 2; min-width: 500px;">
                        <h3>👁️ Vista Previa en Vivo</h3>
                        <p style="color: #666;"><em>La vista previa refleja los cambios una vez que guardas los ajustes.</em></p>
                        <div style="padding: 20px; background: #f0f0f1; border-radius: 8px;">
                            <?php 
                            // Renderizamos el shortcode directamente en el backend para la vista previa
                            echo do_shortcode('[nascor_posts]'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * ==========================================
         * 2. RENDERIZADO DEL SHORTCODE (FRONT Y BACK)
         * ==========================================
         */
        public function render_shortcode( $atts ) {
            ob_start();
            $this->print_dynamic_css(); // CSS con variables del administrador
            $this->render_html();
            $this->print_js();
            return ob_get_clean();
        }

        private function render_html() {
            $categories = get_categories( [ 'hide_empty' => true ] );
            ?>
            <div class="nascor-pg-container">
                <div class="nascor-pg-header">
                    <div class="nascor-pg-search-wrapper">
                        <svg class="nascor-pg-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="none" d="M0 0h24v24H0z"/><path d="M18.031 16.617l4.283 4.282-1.415 1.415-4.282-4.283A8.96 8.96 0 0 1 11 20c-4.968 0-9-4.032-9-9s4.032-9 9-9 9 4.032 9 9a8.96 8.96 0 0 1-1.969 5.617zm-2.006-.742A6.977 6.977 0 0 0 18 11c0-3.86-3.14-7-7-7s-7 3.14-7 7 3.14 7 7 7a6.977 6.977 0 0 0 4.875-1.975l.15-.15z" fill="rgba(255,255,255,0.5)"/></svg>
                        <input type="text" id="nascor-pg-search" class="nascor-pg-input" placeholder="Buscar entrada (mín. 3 letras)..." autocomplete="off">
                    </div>
                    <div class="nascor-pg-filter-wrapper">
                        <select id="nascor-pg-category" class="nascor-pg-select">
                            <option value="all">Todas las categorías</option>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="nascor-pg-grid" class="nascor-pg-grid"></div>
                
                <div id="nascor-pg-loader" class="nascor-pg-loader" style="display: none;">
                    <div class="nascor-spinner"></div>
                </div>
            </div>
            <?php
        }

        /**
         * ==========================================
         * 3. PROCESADOR AJAX (BÚSQUEDA Y FILTRO)
         * ==========================================
         */
        public function ajax_fetch_posts() {
            $search   = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
            $category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : 'all';

            $args = [
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => 12,
            ];

            if ( strlen( $search ) >= 3 ) {
                $args['s'] = $search;
            }

            if ( $category !== 'all' ) {
                $args['cat'] = intval( $category );
            }

            $query = new WP_Query( $args );
            $html = '';

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    
                    $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
                    if ( ! $thumb_url ) {
                        $thumb_url = 'https://via.placeholder.com/600x400/162454/ffffff?text=Nascor';
                    }

                    $title = get_the_title();
                    $raw_content = has_excerpt() ? get_the_excerpt() : get_the_content();
                    $clean_content = preg_replace( '/\[\/?et_[^\]]*\]/', ' ', $raw_content );
                    $clean_content = strip_shortcodes( $clean_content );
                    $clean_content = wp_strip_all_tags( $clean_content );
                    $excerpt = wp_trim_words( $clean_content, 18, '...' );
                    $link = get_the_permalink();
                    
                    $cat_names = wp_list_pluck( get_the_category(), 'name' );
                    $cat_label = ! empty( $cat_names ) ? esc_html( $cat_names[0] ) : '';

                    $html .= '<div class="nascor-pg-card">';
                    $html .= '<div class="nascor-pg-img" style="background-image: url(' . esc_url( $thumb_url ) . ');">';
                    if ( $cat_label ) {
                        $html .= '<span class="nascor-pg-badge">' . $cat_label . '</span>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="nascor-pg-content">';
                    $html .= '<h4 class="nascor-pg-title">' . esc_html( $title ) . '</h4>';
                    $html .= '<p class="nascor-pg-excerpt">' . esc_html( $excerpt ) . '</p>';
                    $html .= '<a href="' . esc_url( $link ) . '" class="nascor-pg-btn">Leer recurso</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                wp_reset_postdata();
            } else {
                $html = '<div class="nascor-pg-no-results">No se encontraron entradas en el blog para mostrar.</div>';
            }

            wp_send_json_success( $html );
        }

        /**
         * ==========================================
         * 4. ESTILOS DINÁMICOS CSS
         * ==========================================
         */
        private function print_dynamic_css() {
            // Obtenemos los colores del administrador
            $bg_top = get_option( 'npg_bg_top', '#162454' );
            $bg_bottom = get_option( 'npg_bg_bottom', '#0d1236' );
            $text_color = get_option( 'npg_text_color', '#ffffff' );
            $btn_bg = get_option( 'npg_btn_bg', '#ffffff' );
            $btn_text = get_option( 'npg_btn_text', '#0d1236' );
            $border_radius = get_option( 'npg_border_radius', '16' );
            ?>
            <style>
                .nascor-pg-container {
                    width: 100%;
                    max-width: 1200px;
                    margin: 0 auto;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .nascor-pg-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 15px;
                    margin-bottom: 30px;
                    flex-wrap: wrap;
                }
                .nascor-pg-search-wrapper, .nascor-pg-filter-wrapper {
                    flex: 1;
                    min-width: 250px;
                    position: relative;
                }
                .nascor-pg-search-icon {
                    position: absolute;
                    left: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    pointer-events: none;
                }
                .nascor-pg-input, .nascor-pg-select {
                    width: 100%;
                    padding: 14px 15px 14px 45px !important;
                    background: <?php echo $bg_top; ?> !important;
                    border: 1px solid rgba(255,255,255,0.15) !important;
                    border-radius: <?php echo $border_radius; ?>px !important;
                    color: <?php echo $text_color; ?> !important;
                    font-size: 15px;
                    outline: none;
                    box-sizing: border-box;
                    transition: all 0.3s ease;
                }
                .nascor-pg-select {
                    padding: 14px 40px 14px 15px;
                    appearance: none;
                    -webkit-appearance: none;
                    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%3E%3Cpath%20fill%3D%22%23FFFFFF%22%20d%3D%22M7%2010l5%205%205-5z%22%2F%3E%3C%2Fsvg%3E") !important;
                    background-repeat: no-repeat !important;
                    background-position: right 10px top 50% !important;
                }
                .nascor-pg-input::placeholder {
                    color: rgba(255,255,255,0.5);
                }
                .nascor-pg-select option {
                    background: <?php echo $bg_bottom; ?>;
                    color: <?php echo $text_color; ?>;
                }
                
                .nascor-pg-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 25px;
                }
                .nascor-pg-card {
                    background: linear-gradient(180deg, <?php echo $bg_top; ?> 0%, <?php echo $bg_bottom; ?> 100%);
                    border-radius: <?php echo $border_radius; ?>px;
                    overflow: hidden;
                    border: 1px solid rgba(255,255,255,0.08);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    display: flex;
                    flex-direction: column;
                    transition: transform 0.3s ease, border-color 0.3s ease;
                }
                .nascor-pg-card:hover {
                    transform: translateY(-5px);
                    border-color: rgba(255,255,255,0.25);
                }
                .nascor-pg-img {
                    height: 180px;
                    background-size: cover;
                    background-position: center;
                    position: relative;
                }
                .nascor-pg-badge {
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    background: rgba(0,0,0,0.6);
                    backdrop-filter: blur(5px);
                    color: #fff;
                    font-size: 11px;
                    font-weight: 600;
                    padding: 5px 12px;
                    border-radius: 20px;
                    text-transform: uppercase;
                }
                .nascor-pg-content {
                    padding: 22px;
                    display: flex;
                    flex-direction: column;
                    flex-grow: 1;
                }
                .nascor-pg-title {
                    color: <?php echo $text_color; ?>;
                    font-size: 18px;
                    margin: 0 0 10px 0;
                    line-height: 1.3;
                    font-weight: 700;
                }
                .nascor-pg-excerpt {
                    color: <?php echo $text_color; ?>;
                    opacity: 0.8;
                    font-size: 14px;
                    line-height: 1.5;
                    margin: 0 0 20px 0;
                    flex-grow: 1;
                }
                .nascor-pg-btn {
                    display: inline-block;
                    text-align: center;
                    background: <?php echo $btn_bg; ?>;
                    color: <?php echo $btn_text; ?> !important;
                    text-decoration: none !important;
                    padding: 10px 15px;
                    border-radius: 8px;
                    font-weight: bold;
                    font-size: 14px;
                    transition: all 0.3s ease;
                    margin-top: auto;
                }
                .nascor-pg-btn:hover {
                    opacity: 0.9;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
                .nascor-pg-no-results {
                    grid-column: 1 / -1;
                    text-align: center;
                    color: #666;
                    padding: 40px;
                    background: rgba(0,0,0,0.05);
                    border-radius: 16px;
                }
                .nascor-pg-loader {
                    display: flex;
                    justify-content: center;
                    padding: 40px;
                    grid-column: 1 / -1;
                }
                .nascor-spinner {
                    width: 40px;
                    height: 40px;
                    border: 4px solid rgba(0,0,0,0.1);
                    border-left-color: <?php echo $bg_top; ?>;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin { 100% { transform: rotate(360deg); } }

                @media (max-width: 768px) {
                    .nascor-pg-header { flex-direction: column; }
                    .nascor-pg-search-wrapper, .nascor-pg-filter-wrapper { width: 100%; }
                    .nascor-pg-grid { grid-template-columns: 1fr; }
                }
            </style>
            <?php
        }

        /**
         * ==========================================
         * 5. JAVASCRIPT AJAX Y LISTENERS
         * ==========================================
         */
        private function print_js() {
            $ajax_url = admin_url( 'admin-ajax.php' );
            $nonce    = wp_create_nonce( 'nascor_pg_nonce' );
            ?>
            <script type="text/javascript">
                // Usamos un closure (IIFE) o variables únicas para evitar choques en admin
                (function() {
                    document.addEventListener("DOMContentLoaded", function() {
                        // Seleccionar usando querySelectorAll por si hay múltiples instancias o en vista previa
                        const containers = document.querySelectorAll(".nascor-pg-container");
                        
                        containers.forEach(container => {
                            const searchInput = container.querySelector(".nascor-pg-input");
                            const categorySelect = container.querySelector(".nascor-pg-select");
                            const grid = container.querySelector(".nascor-pg-grid");
                            const loader = container.querySelector(".nascor-pg-loader");
                            let timeoutId;

                            async function fetchNascorPosts() {
                                if(!grid) return;
                                const searchTerm = searchInput ? searchInput.value.trim() : '';
                                const category = categorySelect ? categorySelect.value : 'all';

                                grid.innerHTML = "";
                                if(loader) loader.style.display = "flex";

                                const formData = new FormData();
                                formData.append('action', 'nascor_fetch_posts');
                                formData.append('security', '<?php echo $nonce; ?>');
                                formData.append('search', searchTerm);
                                formData.append('category', category);

                                try {
                                    const response = await fetch("<?php echo $ajax_url; ?>", {
                                        method: "POST",
                                        body: formData
                                    });
                                    const data = await response.json();
                                    
                                    if(loader) loader.style.display = "none";
                                    
                                    if(data.success) {
                                        grid.innerHTML = data.data;
                                    } else {
                                        grid.innerHTML = '<div class="nascor-pg-no-results">Error de conexión.</div>';
                                    }
                                } catch (error) {
                                    if(loader) loader.style.display = "none";
                                    grid.innerHTML = '<div class="nascor-pg-no-results">Error al cargar los artículos.</div>';
                                }
                            }

                            fetchNascorPosts();

                            if (searchInput) {
                                searchInput.addEventListener("input", function() {
                                    clearTimeout(timeoutId);
                                    const val = this.value.trim();
                                    if (val.length >= 3 || val.length === 0) {
                                        timeoutId = setTimeout(() => { fetchNascorPosts(); }, 500);
                                    }
                                });
                            }

                            if (categorySelect) {
                                categorySelect.addEventListener("change", fetchNascorPosts);
                            }
                        });
                    });
                })();
            </script>
            <?php
        }
    }

    new Nascor_Post_Grid_Plugin();
}