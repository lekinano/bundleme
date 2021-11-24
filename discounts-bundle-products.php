<?php

/*
Plugin Name: BundleMe
Description: Bundle your Product easily!
Version: 1.2
Author: Kinane (kinane.co)
Text Domain: discounts-bundle-products
*/



if (
    in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )
) {



    // init cart 
    add_action('init', 'dbp_init');
}


function dbp_init()
{



    if (@$_POST['dbp_add_to_cart']) {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();
        $attributes     =  $_POST['dbp_varition'][$_POST['product_bundle']];
        $product_id     =  $_POST['dbp_product_id'];
        $attributes = $attributes ? $attributes : array();
        array_walk_recursive($attributes, function (&$v) {
            $v = trim($v);
        });
        $product = wc_get_product($product_id);
        if ($attributes) {
            foreach ($attributes   as  $attribute) {
                $data_store   = WC_Data_Store::load('product');
                $var_id  = $data_store->find_matching_product_variation(
                    $product,
                    $attribute
                );
                $woocommerce->cart->add_to_cart($product_id, 1,  $var_id,  $attribute, array('bundle_product' =>  true));
            }
        } else {
            $woocommerce->cart->add_to_cart($product_id, $_POST['product_bundle'], '' , '' , array('bundle_product' =>  true));
        }
    }


    // form shortcode 
    add_shortcode('product-bundle', 'dbp_bundle_shortcode');
    // add form in page 
    // add_action('woocommerce_before_add_to_cart_form', 'dbp_additional_button');
    // add backend
    add_action('woocommerce_product_options_general_product_data', 'dbp_woocommerce_product_custom_fields');
    // save backend
    add_action('woocommerce_process_product_meta', 'dbp_woocommerce_product_custom_fields_save');
    // update cart 
    add_action('woocommerce_cart_calculate_fees', 'dbp_recalculate');

    add_action('init', 'dbp_load_textdomain');
}


function dbp_load_textdomain()
{
    load_plugin_textdomain('discounts-bundle-products', false, dirname(plugin_basename(__FILE__)) . '/');
}


function dbp_additional_button()
{
    echo do_shortcode("[product-bundle]");
}

function dbp_bundle_shortcode()
{

    global $post;
    global $product;
    if (!$product) {
        return '';
    }
    if (!get_post_meta($post->ID, 'is_bundle', true)) {
        return;
    }
    ob_start();
    include  plugin_dir_path(__FILE__) . 'template/bundle-template.php';
    $message  = ob_get_contents();
    ob_end_clean();
    return $message;
}





function dbp_woocommerce_product_custom_fields()
{
    global $woocommerce, $post;

    woocommerce_wp_checkbox(
        array(
            'id' => 'is_bundle',
            'name' => 'is_bundle',
            'placeholder' => 'Is Bundle product?',
            'label' => __('Is Bundle product? ', 'woocommerce'),
            'desc_tip' => 'true'
        )
    );
    for ($i = 0; $i < 3; $i++) {

        echo '<div class="product_custom_field options_group">';
        woocommerce_wp_text_input(
            array(
                'id' => '_dbp_total_count_' . $i,
                'name' => '_dbp_total_count_' . $i,
                'placeholder' => 'Total Product in bundle',
                'label' => __('Total Product in bundle '. $i , 'woocommerce'),
                'desc_tip' => 'true'
            )
        );

        woocommerce_wp_text_input(
            array(
                'id' => '_bundle_free_product_' . $i,
                'name' => '_bundle_free_product_' . $i,
                'placeholder' => 'Bundle Free Product ',
                'label' => __('No of Free Product ', 'woocommerce'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0'
                )
            )
        );

        woocommerce_wp_textarea_input(
            array(
                'id' => '_bundle_title_' . $i,
                'name' => '_bundle_title_' . $i,
                'placeholder' => 'Bundle Title',
                'label' => __('Bundle Title', 'woocommerce')
            )
        );

        woocommerce_wp_textarea_input(
            array(
                'id' => '_bundle_title_before_' . $i,
                'name' => '_bundle_title_before_' . $i,
                'placeholder' => 'Bundle Title before text',
                'label' => __('Bundle Title before text', 'woocommerce')
            )
        );

        echo '</div>';
    }
}

function dbp_woocommerce_product_custom_fields_save($post_id)
{

    $keys =    array(
        '_bundle_title_',
        '_bundle_free_product_',
        '_dbp_total_count_',
        '_bundle_title_before_'
    );

    $woocommerce_custom_product_text_field = $_POST['is_bundle'];
    if (!empty($woocommerce_custom_product_text_field)) {
        update_post_meta($post_id, 'is_bundle', ($woocommerce_custom_product_text_field));
    }

    for ($i = 0; $i < 3; $i++) {
        foreach ($keys as $key) {
            $woocommerce_custom_product_text_field = $_POST[$key . $i];
            if (!empty($woocommerce_custom_product_text_field)) {
                update_post_meta($post_id, $key . $i, ($woocommerce_custom_product_text_field));
            }
        }
    }
}

function dbp_recalculate(WC_Cart $cart)
{

    if ($cart->cart_contents_count < 3) return;
    $products = array();
    foreach ($cart->get_cart() as $cart_item_key => $values) {
        $_product = $values['data'];
        $price = $_product->get_price_including_tax();
        if (!get_post_meta($values['product_id'], 'is_bundle', true)) {
            continue;
        }
        $products[$values['product_id']]['price'][] =  $price;

        if (!isset($products[$values['product_id']]['count'])) {
            $products[$values['product_id']]['count'] = 0;
        }

        $products[$values['product_id']]['count'] = $products[$values['product_id']]['count']  + $values['quantity'];
        if (!isset($products[$values['product_id']]['bundle'])) {
            $products[$values['product_id']]['bundle']  = array();
            for ($i = 0; $i < 3; $i++) {
                $bundle_free_product = get_post_meta($values['product_id'], '_bundle_free_product_' . $i, true);
                $dbp_total_count  = get_post_meta($values['product_id'], '_dbp_total_count_' . $i, true);
                if (!empty($dbp_total_count)) {
                    $bundle_free_product = $bundle_free_product ? $bundle_free_product : 0;
                    $products[$values['product_id']]['bundle'][$dbp_total_count] = array(
                        'bundle_free_product'   =>  $bundle_free_product,
                        'dbp_total_count'       =>  $dbp_total_count
                    );
                }
            }
        }
    }

    $discount_amount = calculate_discount($products);
    $text = get_option('dbp_offer_title') ? get_option('dbp_offer_title') : 'Offer';

    $cart->add_fee($text, -$discount_amount, false);
}



function calculate_discount(&$products,    $discount_amount = 0)
{
    if (empty($products)) {
        return   $discount_amount;
    }

    foreach ($products as $product_id => $product) {
        ksort($product['bundle']);
        $free_items = 0;
        foreach ($product['bundle'] as $totale => $bundel) {
            if ($totale != 1) {
                if ($totale <= $product['count']) {
                    $free_items =  $bundel['bundle_free_product'];
                    $finel_total = $bundel['dbp_total_count'];
                }
            }
        }
        if ($free_items == 0) {
            unset($products[$product_id]);
            continue;
        }
        for ($i = 1; $i <= $free_items; $i++) {
            $discount_amount = $discount_amount + $product['price'][0];
        }

        $products[$product_id]['count'] = $products[$product_id]['count'] - $finel_total;
    }

    $discount_amount =  calculate_discount($products, $discount_amount);
    return   $discount_amount;
}


add_filter('woocommerce_get_sections_products', 'dbp_add_section');
function dbp_add_section($sections)
{

    $sections['discounts-bundle-products'] = __('Discounts Bundle products', 'text-domain');
    return $sections;
}

add_filter('woocommerce_get_settings_products', 'dbp_all_settings', 10, 2);
function dbp_all_settings($settings, $current_section)
{
    /**
     * Check the current section is what we want
     **/
    if ($current_section == 'discounts-bundle-products') {
        $settings_slider = array();
        // Add Title to the Settings
        $settings_slider[] = array('name' => __('Offer title in cart', 'text-domain'), 'type' => 'title', 'desc' => __('The following options are used to configure Bundle plugin', 'text-domain'), 'id' => 'bundele1');
        // Add first checkbox option

        $settings_slider[] = array(
            'name'     => __('Offer Title', 'text-domain'),
            'desc_tip' => __('This will add a title to your Offer', 'text-domain'),
            'id'       => 'dbp_offer_title',
            'type'     => 'text',
            'desc'     => __('Any title you want can be added to your Offer with this option!', 'text-domain'),
        );

        $settings_slider[] = array('type' => 'sectionend', 'id' => 'bundele2');
        return $settings_slider;

        /**
         * If not, return the standard settings
         **/
    } else {
        return $settings;
    }
}
