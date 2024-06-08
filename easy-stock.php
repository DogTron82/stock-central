<?php
/*
Plugin Name: Easy Stock Management
Description: A simple plugin to manage regular and secondary stock with prices.
Version: 1.4
Author: Gustav Öman
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add admin menu
add_action( 'admin_menu', 'esm_admin_menu' );

function esm_admin_menu() {
    add_menu_page(
        'Easy Stock Management',
        'Stock Management',
        'manage_woocommerce',
        'easy-stock-management',
        'esm_stock_manager_page',
        'dashicons-products',
        58
    );
}

function esm_stock_manager_page() {
    // Check if the user is allowed to update options
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    // Handle form submission
    if ( isset( $_POST['esm_save_changes'] ) && check_admin_referer( 'esm_save_stock', 'esm_stock_nonce' ) ) {
        esm_save_stock_changes();
    }

    // Search functionality
    $search_query = isset( $_GET['esm_search'] ) ? sanitize_text_field( $_GET['esm_search'] ) : '';

    // Number of products per page
    $posts_per_page = isset( $_GET['esm_posts_per_page'] ) ? intval( $_GET['esm_posts_per_page'] ) : 20;

    // Pagination settings
    $paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;

    // Fetch products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
    );

    if ( ! empty( $search_query ) ) {
        $args['s'] = $search_query;
    }

    $products = new WP_Query( $args );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Stock Central', 'easy-stock-management' ); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="easy-stock-management" />
            <input type="text" name="esm_search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search products...', 'easy-stock-management' ); ?>" />
            <input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'easy-stock-management' ); ?>" />
            <label for="esm_posts_per_page"><?php esc_html_e( 'Products per page:', 'easy-stock-management' ); ?></label>
            <select name="esm_posts_per_page" id="esm_posts_per_page" onchange="this.form.submit()">
                <option value="10" <?php selected( $posts_per_page, 10 ); ?>>10</option>
                <option value="20" <?php selected( $posts_per_page, 20 ); ?>>20</option>
                <option value="50" <?php selected( $posts_per_page, 50 ); ?>>50</option>
                <option value="100" <?php selected( $posts_per_page, 100 ); ?>>100</option>
            </select>
        </form>
        <?php esm_render_pagination( $products->max_num_pages, $paged ); ?>
        <form method="post">
            <?php wp_nonce_field( 'esm_save_stock', 'esm_stock_nonce' ); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Image', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Product Name', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'SKU', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Regular Stock', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Secondary Stock (Kragerø)', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Price', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Sale Price', 'easy-stock-management' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'easy-stock-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $products->have_posts() ) : ?>
                        <?php while ( $products->have_posts() ) : $products->the_post(); ?>
                            <?php
                            $product_id = get_the_ID();
                            $product_obj = wc_get_product( $product_id );

                            // Display the main product
                            $regular_stock = $product_obj->get_stock_quantity();
                            $secondary_stock = get_post_meta( $product_id, '_secondary_stock', true );
                            $price = $product_obj->get_regular_price();
                            $sale_price = $product_obj->get_sale_price();
                            $product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'thumbnail' );
                            $product_type = $product_obj->get_type();
                            ?>
                            <tr class="product-row" data-product-id="<?php echo esc_attr( $product_id ); ?>">
                                <td><?php echo esc_html( $product_id ); ?></td>
                                <td><?php if ( $product_image ) { echo '<img src="' . esc_url( $product_image[0] ) . '" alt="' . esc_attr( get_the_title() ) . '" width="50" height="50">'; } ?></td>
                                <td><?php echo esc_html( get_the_title() ); ?></td>
                                <td><?php echo esc_html( ucfirst( $product_type ) ); ?></td>
                                <td><?php echo esc_html( $product_obj->get_sku() ); ?></td>
                                <?php if ( $product_type === 'simple' ) : ?>
                                    <td><input type="number" name="regular_stock[<?php echo esc_attr( $product_id ); ?>]" value="<?php echo esc_attr( $regular_stock ); ?>" /></td>
                                    <td><input type="number" name="secondary_stock[<?php echo esc_attr( $product_id ); ?>]" value="<?php echo esc_attr( $secondary_stock ); ?>" /></td>
                                    <td><input type="text" name="price[<?php echo esc_attr( $product_id ); ?>]" value="<?php echo esc_attr( $price ); ?>" /></td>
                                    <td><input type="text" name="sale_price[<?php echo esc_attr( $product_id ); ?>]" value="<?php echo esc_attr( $sale_price ); ?>" /></td>
                                <?php else : ?>
                                    <td colspan="4"></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ( $product_type === 'simple' ) : ?>
                                        <button type="submit" name="esm_save_changes" value="<?php echo esc_attr( $product_id ); ?>" class="button button-primary"><?php esc_attr_e( 'Save', 'easy-stock-management' ); ?></button>
                                    <?php elseif ( $product_type === 'variable' ) : ?>
                                        <button type="button" class="button toggle-variations"><?php esc_attr_e( 'Show Variations', 'easy-stock-management' ); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            // If the product is a variable product, display its variations
                            if ( $product_obj->is_type( 'variable' ) ) {
                                $variations = $product_obj->get_children();
                                foreach ( $variations as $variation_id ) {
                                    $variation = wc_get_product( $variation_id );
                                    $regular_stock = $variation->get_stock_quantity();
                                    $secondary_stock = get_post_meta( $variation_id, '_secondary_stock', true );
                                    $price = $variation->get_regular_price();
                                    $sale_price = $variation->get_sale_price();
                                    ?>
                                    <tr class="variation-row" data-parent-id="<?php echo esc_attr( $product_id ); ?>" style="display: none;">
                                        <td><?php echo esc_html( $variation_id ); ?></td>
                                        <td></td>
                                        <td><?php echo '&mdash; ' . esc_html( get_the_title( $variation_id ) ); ?></td>
                                        <td><?php echo esc_html( 'Variation' ); ?></td>
                                        <td><?php echo esc_html( $variation->get_sku() ); ?></td>
                                        <td><input type="number" name="regular_stock[<?php echo esc_attr( $variation_id ); ?>]" value="<?php echo esc_attr( $regular_stock ); ?>" /></td>
                                        <td><input type="number" name="secondary_stock[<?php echo esc_attr( $variation_id ); ?>]" value="<?php echo esc_attr( $secondary_stock ); ?>" /></td>
                                        <td><input type="text" name="price[<?php echo esc_attr( $variation_id ); ?>]" value="<?php echo esc_attr( $price ); ?>" /></td>
                                        <td><input type="text" name="sale_price[<?php echo esc_attr( $variation_id ); ?>]" value="<?php echo esc_attr( $sale_price ); ?>" /></td>
                                        <td>
                                            <button type="submit" name="esm_save_changes" value="<?php echo esc_attr( $variation_id ); ?>" class="button button-primary"><?php esc_attr_e( 'Save', 'easy-stock-management' ); ?></button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="10"><?php esc_html_e( 'No products found.', 'easy-stock-management' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        <?php esm_render_pagination( $products->max_num_pages, $paged ); ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.toggle-variations').forEach(function (button) {
                button.addEventListener('click', function () {
                    const parentRow = button.closest('tr');
                    const parentId = parentRow.dataset.productId;
                    document.querySelectorAll(`.variation-row[data-parent-id="${parentId}"]`).forEach(function (row) {
                        row.style.display = row.style.display === 'none' ? '' : 'none';
                    });
                    button.textContent = button.textContent === 'Show Variations' ? 'Hide Variations' : 'Show Variations';
                });
            });
        });
    </script>
    <?php
}

function esm_render_pagination( $total_pages, $current_page ) {
    if ( $total_pages > 1 ) {
        $prev_page = max( 1, $current_page - 1 );
        $next_page = min( $total_pages, $current_page + 1 );
        ?>
        <div class="tablenav top">
            <div class="tablenav-pages" style="margin-bottom: 10px; text-align: center;">
                <?php
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '&laquo;', 'easy-stock-management' ),
                    'next_text' => __( '&raquo;', 'easy-stock-management' ),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'end_size' => 2,
                    'mid_size' => 2,
                ) );
                ?>
            </div>
            <div class="tablenav-pages" style="margin-bottom: 10px; text-align: center;">
                <a href="<?php echo esc_url( add_query_arg( 'paged', $prev_page ) ); ?>" class="button"><?php esc_html_e( 'Previous', 'easy-stock-management' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $next_page ) ); ?>" class="button"><?php esc_html_e( 'Next', 'easy-stock-management' ); ?></a>
            </div>
        </div>
        <?php
    }
}

function esm_save_stock_changes() {
    if ( isset( $_POST['esm_save_changes'] ) ) {
        $product_id = intval( $_POST['esm_save_changes'] );
        $product_obj = wc_get_product( $product_id );

        if ( ! is_object( $product_obj ) ) {
            return;
        }

        // Update regular stock
        if ( isset( $_POST['regular_stock'][ $product_id ] ) ) {
            $product_obj->set_stock_quantity( wc_clean( $_POST['regular_stock'][ $product_id ] ) );
        }

        // Update secondary stock
        if ( isset( $_POST['secondary_stock'][ $product_id ] ) ) {
            $secondary_stock = wc_clean( $_POST['secondary_stock'][ $product_id ] );
            update_post_meta( $product_id, '_secondary_stock', $secondary_stock );
        }

        // Update price
        if ( isset( $_POST['price'][ $product_id ] ) ) {
            $product_obj->set_regular_price( wc_clean( $_POST['price'][ $product_id ] ) );
        }

        // Update sale price
        if ( isset( $_POST['sale_price'][ $product_id ] ) ) {
            $product_obj->set_sale_price( wc_clean( $_POST['sale_price'][ $product_id ] ) );
        }

        $product_obj->save();

        // Add admin notice
        add_action( 'admin_notices', 'esm_save_success_notice' );
    }
}

function esm_save_success_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Stock and prices updated successfully.', 'easy-stock-management' ); ?></p>
    </div>
    <?php
}
