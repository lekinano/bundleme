<?php

global $product;
$attributes = $product->get_attributes();
$variations_id = array();
for ($i = 0; $i < 3; $i++) {
    if (get_post_meta($post->ID, '_dbp_total_count_' . $i, true)) {
        $bundles[get_post_meta($post->ID, '_dbp_total_count_' . $i, true)] = array(
            'count' => get_post_meta($post->ID, '_dbp_total_count_' . $i, true),
            'title' => get_post_meta($post->ID, '_bundle_title_' . $i, true),
            'free_product' => get_post_meta($post->ID, '_bundle_free_product_' . $i, true),
            'text_before' => get_post_meta($post->ID, '_bundle_title_before_' . $i, true),
        );
    }
}
?>

<form class="dbp_forms_product" method="post">
    <div>
        <div class="dbp_wrapper">
            <?php
            foreach ($bundles as $key => $bundle) {

                $bundle['count'] = $bundle['count'] ? $bundle['count'] : 0;
                $bundle['free_product'] = $bundle['free_product'] ? $bundle['free_product'] : 0;

                if ($bundle['count'] == 0 && $bundle['free_product'] == 0) {
                    continue;
                }


            ?>
                <div class="dbp_form">
                    <label class="dbp_label" for="dbp_<?php echo $key ?>">
                        <div class="offer"><?php echo $bundle['text_before'] ?>
                        </div>
                        <div class="dbp_radio_button">
                            <input value="<?php echo $key ?>" id="dbp_<?php echo $key ?>" name="product_bundle" type="radio">
                        </div>
                        <div class="dbp_title">
                            <h5><?php echo $bundle['title'] ?></h5>
                        </div>
                        <br>
                        <div>
                            <?php
                            $symbol = get_woocommerce_currency_symbol();
                            $product_count =  @$bundle['count'] - @$bundle['free_product'];
                            $regular = 0;
                            $sale  = 0;
                            if ($product->is_type('variable')) {
                                $product_variations = $product->get_available_variations();
                                $variation_product_id = $product_variations[0]['variation_id'];
                                $variation_product = new WC_Product_Variation($variation_product_id);
                                $regular =  $variation_product->regular_price;
                                $sale  = $variation_product->sale_price;
                            } else {
                                $regular =  $product->regular_price;
                                $sale = $product->sale_price;
                            }
                            if(!$sale){
                                $sale =  $regular;
                            }
                            $regular = $regular  *  @$bundle['count'];
                            $sale = $sale  *  $product_count;

                            ?>


                            <span class="woocommerce-Price-amount amount"> <?php echo  $symbol . $sale ?></span>
                            <del><span class="woocommerce-Price-amount amount"><?php echo  $symbol .  $regular ?></span></del>
                        </div>


                        <div class="dbp_show_me">
                            <input type="hidden" name="count_of_product" value="<?php echo $bundle['count'] ?>">
                            <input type="hidden" name="price_of_product" value="<?php echo $sale ?>">

                            <?php
                            for ($i =  0; $i < $bundle['count']; $i++) {
                                if ($attributes) {
                                    echo "<br> <span class='product-title'>";
                                    echo $i + 1;
                                    echo  '. ' . $product->get_name();
                                    echo " </span>     <div>";
                                    foreach ($attributes as $attribute) {
                                        echo "<div class='dbp_attribute'>";
                                        echo '<div  class="dbp_name" > ' . $attribute['name'] . ": </div> <div class='dbp_selector'>";
                                        $product_attributes = array();
                                        $product_attributes = explode('|', $attribute['value']);
                                        $attributes_dropdown = '<select name="dbp_varition[' . $key . '][' . $i . '][' . 'attribute_' . sanitize_title($attribute['name']) . ']">';
                                        $attributes_dropdown .= '<option value="">Choose an option</option>';
                                        foreach ($product_attributes as $pa) {
                                            $attributes_dropdown .= '<option value="' . $pa . '">' . $pa . '</option>';
                                        }
                                        $attributes_dropdown .= '</select></div></div>';
                                        echo $attributes_dropdown;
                                    }
                                    echo "</div>";
                                }
                            }
                            ?>

                        </div>
                    </label>
                </div>
            <?php } ?>
        </div>
    </div>

    <input type="hidden" name="action" value="xoo_wsc_add_to_cart">
    <input type="hidden" name="product_id" value="<?php echo 0 ?>">
    <input type="hidden" name="add-to-cart" value="<?php echo 0 ?>">
    <input type="hidden" name="add-to-cart" value="<?php echo 0 ?>">


    <input type="hidden" name="dbp_add_to_cart" value="true">
    <input type="hidden" name="dbp_product_id" value="<?php echo $post->ID ?>">
    <input type="hidden" name="dbp_variatons_id" value="<?php echo join(',', $variations_id) ?>">
    <button type="submit" class="dbp_cart_btn single_add_to_cart_button button alt added"><?php echo _e('ADD TO CART') ?>
        <span class="xoo-wsc-icon-spinner2 xoo-wsc-icon-atc xoo-wsc-active"></span>
        <span class="xoo-wsc-icon-checkmark xoo-wsc-icon-atc"></span>
    </button>
</form>

<script>
    jQuery(
        function($) {
            $('.dbp_cart_btn').find('.xoo-wsc-icon-spinner2').hide();
            $('.dbp_cart_btn').find('.xoo-wsc-icon-checkmark').hide();

            $('.dbp_show_me').hide();
            $('.dbp_form ').click(
                function() {
                    $('.dbp_show_me select').prop('required', false);
                    $('.dbp_show_me').hide();
                    $('.dbp_form').removeClass('active');
                    $(this).addClass('active');
                    $(this).find('.dbp_show_me').show();
                    $(this).find('select').prop('required', true);
                }
            );

            $(document).on("submit", ".dbp_forms_product", function(e) {
                e.preventDefault();
                var length = $(this).find('input[type=radio]:checked').length;
                if (length == 0) {
                    return;
                }
                if ($(this).data('requestRunning')) {
                    return;
                }
                $(this).data('requestRunning', true);
                var form = $(this);
                var url = form.attr('action');
                $('.dbp_cart_btn').find('.xoo-wsc-icon-spinner2').show();
                $('.dbp_cart_btn').find('.xoo-wsc-icon-checkmark').hide();
                $.ajax({
                    type: "POST",
                    url: '?wc-ajax=xoo_wsc_add_to_cart',
                    data: form.serialize(),
                    success: function(json_data) {
                        $.each(json_data.fragments,
                            function(i, v) {
                                $(String(i)).html(v);
                            }
                        );
                        setTimeout(
                            function() {
                                $('.dbp_cart_btn').find('.xoo-wsc-icon-spinner2').hide();
                                $('.dbp_cart_btn').find('.xoo-wsc-icon-checkmark').show();
                                $('.xoo-wsc-modal').addClass('xoo-wsc-active');
                                $('.xoo-wsc-updating').hide();
                                $(form).data('requestRunning', false);
                            },
                            50
                        );
                    }
                });

                if (typeof snaptr === 'function') {
                    snaptr('track', 'ADD_CART', {
                        'currency': '<?php echo get_option('woocommerce_currency') ?>',
                        'price': $('.dbp_form.active').find('[name="price_of_product"]').val(),
                        'item_ids': [<?php echo $post->ID ?>]
                    });
                }
                if (typeof pintrk === 'function') {
                    pintrk('track', 'AddToCart', {
                        value: $('.dbp_form.active').find('[name="price_of_product"]').val(),
                        order_quantity: $('.dbp_form.active').find('[name="count_of_product"]').val(),
                        currency: '<?php echo get_option('woocommerce_currency') ?>'
                    });
                }
            });
        }
    );
</script>
<style>
    .dbp_wrapper {
        margin-top: 10px;
    }

    .dbp_title,
    .dbp_radio_button {
        display: inline-block;
    }

    .dbp_label {
        padding: 10px;
        width: 100%;
        border: 1px solid;
        border-radius: 5px;
        padding: 20px;
    }

    .dbp_name {
        margin-bottom: 10px;
        margin-top: 10px;
    }

    .dbp_cart_btn {
        width: 100%;
    }

    .dbp_form,
    .dbp_forms_product {
        margin-bottom: 10px;
        margin-top: 10px;
    }

    .dbp_attribute {
        display: inline-block;
        width: 48%;
        margin-left: 10px;
    }

    /* .dbp_cart_btn span.xoo-wsc-icon-atc {
        display: none;
    } */
</style>