<?php
/*
Plugin Name: Partners StandPoint
Plugin URI: http://pixel-web.in.ua/
Description: Плагин написан специально для сайта standpoint.com.ua. 
Version: 3.35
Author: Yurii Bevzenko
Author email: bevvzenko5@gmail.com/
License: GPLv2
*/

function my_custom_post_partner() {
    $labels = array(
        'name'               => _x( 'Все партнеры', 'post type general name' ),
        'singular_name'      => _x( 'Партнеры', 'post type singular name' ),
        'add_new'            => _x( 'Добавить нового партнера', '  partners_post' ),
        'add_new_item'       => __( 'Добавить нового партнера' ),
        'edit_item'          => __( 'Редактировать информацию о партнере' ),
        'new_item'           => __( 'Новый парнер' ),
        'all_items'          => __( 'Все партнеры' ),
        'view_item'          => __( 'Просмотр партнеров' ),
        'search_items'       => __( 'Поиск партнеров' ),
        'not_found'          => __( 'Партнер не найден' ),
        'not_found_in_trash' => __( 'Партнер не найдена в Корзине' ), 
        'parent_item_colon'  => '',
        'menu_name'          => 'Наши партнеры'
    );
    $args = array(
        'labels'        => $labels,
        'description'   => 'Holds our portfolio ',
        'public'        => true,
        'menu_position' => 5,
        'supports'      => array( 'title', 'thumbnail'),
        'has_archive'   => true,
    );
    register_post_type( ' partner', $args );    
}
add_action( 'init', 'my_custom_post_partner' );

// подключаем функцию активации мета блока (my_extra_fields_partners)
add_action('add_meta_boxes', 'my_extra_fields_partners', 1);

function my_extra_fields_partners() {
    add_meta_box( 'extra_fields_partners', 'Линк на сайт партнера', 'extra_fields_partners_box_func', 'partner', 'normal', 'high'  );
}

function extra_fields_partners_box_func( $post ){
    ?>
    <p><label><input type="text" name="extra[link_partner]" value="<?php echo get_post_meta($post->ID, 'link_partner', 1); ?>" style="width:50%" /> Линк на сайт парнера</label></p>
    <input type="hidden" name="extra_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
    <?php
}

// включаем обновление полей при сохранении
add_action('save_post', 'my_extra_fields_partners_update', 0);

/* Сохраняем данные, при сохранении поста */
function my_extra_fields_partners_update( $post_id ){
    if ( !wp_verify_nonce($_POST['extra_fields_nonce'], __FILE__) ) return false; // проверка
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false; // выходим если это автосохранение
    if ( !current_user_can('edit_post', $post_id) ) return false; // выходим если юзер не имеет право редактировать запись

    if( !isset($_POST['extra']) ) return false; // выходим если данных нет

    // Все ОК! Теперь, нужно сохранить/удалить данные
    $_POST['extra'] = array_map('trim', $_POST['extra']); // чистим все данные от пробелов по краям
    foreach( $_POST['extra'] as $key=>$value ){
        var_dump($key);
        if( empty($value) ){
            delete_post_meta($post_id, $key); // удаляем поле если значение пустое
            continue;
        }

        update_post_meta($post_id, $key, $value); // add_post_meta() работает автоматически
    }
    return $post_id;
}

function true_include_myuploadscript() {
    // у вас в админке уже должен быть подключен jQuery, если нет - раскомментируйте следующую строку:
    // wp_enqueue_script('jquery');
    // дальше у нас идут скрипты и стили загрузчика изображений WordPress
    if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
    // само собой - меняем admin.js на название своего файла
    wp_enqueue_script( 'myuploadscript', plugins_url() . '/partners/js/admin.js', array('jquery'), null, false );
}
 
add_action( 'admin_enqueue_scripts', 'true_include_myuploadscript' );

function true_image_uploader_field( $name, $value = '', $w = 115, $h = 90, $arrImg='') {
    $default = plugins_url() . '/partners/img/no_image.jpg';

    if( $value ) {
        $image_attributes = wp_get_attachment_image_src( $value, array($w, $h) );
        //var_dump($image_attributes);
        $src = $image_attributes[0];
    } else {
        $src = $default;
    }
   if($name=='uploader_custom') $title_block = 'Картинка бесцветная';
   if($name=='uploader_custom_2') $title_block = 'Картинка цветная';
   echo $title_block;
    echo '
    <div>
        <img data-src="' . $default . '" src="' . $src . '" width="' . $w . 'px" height="' . $h . 'px" />
        <div>
            <input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $value . '" />
            <button type="submit" class="upload_image_button button">Загрузить</button>
            <button type="submit" class="remove_image_button button">&times;</button>
        </div>
    </div>
    ';
}

function true_meta_boxes_u() {
    add_meta_box('truediv', 'Настройки', 'true_print_box_u', 'partner', 'normal', 'high');
}
 
add_action( 'admin_menu', 'true_meta_boxes_u' );
 
/*
 * Заполняем метабокс
 */
function true_print_box_u($post) {
    //print_r($post);

    if( function_exists( 'true_image_uploader_field' ) ) {

        $arrImg = array(get_post_meta($post->ID, 'uploader_custom',true), get_post_meta($post->ID, 'uploader_custom_2',true) );

        $attachments = get_attached_media( 'image', $post->ID );
        $imgCountPost = array();
        foreach ($attachments as $countIMG) {
           $imgCountPost[]=$countIMG->ID;
        }
        $result = array_diff($imgCountPost, $arrImg);
        for($i=1; $i <= count($result); $i++){
             wp_delete_attachment($result[$i], true) ;
        } 
        ?>
<table>
    <tr><td>
        <?php  true_image_uploader_field( 'uploader_custom', get_post_meta($post->ID, 'uploader_custom',true) ); ?>
        </td><td>
        <?php  true_image_uploader_field( 'uploader_custom_2', get_post_meta($post->ID, 'uploader_custom_2',true) ); ?>
       </td> 
    </tr>    
</table>
        <?php
        
    }
}
 
/*
 * Сохраняем данные произвольного поля
 */
function true_save_box_data_u( $post_id ) {
    //echo $post_id;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return $post_id;
    update_post_meta( $post_id, 'uploader_custom', $_POST['uploader_custom']);
    update_post_meta( $post_id, 'uploader_custom_2', $_POST['uploader_custom_2']);
    return $post_id;

}
 
add_action('save_post', 'true_save_box_data_u');

function true_add_options_page_u() {
    if ( isset( $_GET['page'] ) == 'uplsettings' ) {
        if ( 'save' == isset( $_REQUEST['action'] ) ) {

            update_option('uploader_custom', $_REQUEST[ 'uploader_custom' ]);
            update_option('uploader_custom_2', $_REQUEST[ 'uploader_custom_2' ]);
            header("Location: ". site_url() ."/wp-admin/options-general.php?page=uplsettings&saved=true");
            die;
        }
    }
    add_submenu_page('options-general.php','Дополнительные настройки','Настройки','edit_posts', 'uplsettings', 'true_print_options_u');
}
 
function true_print_options_u() {
    if ( isset( $_REQUEST['saved'] ) ){
        echo '<div class="updated"><p>Сохранено.</p></div>';
    }

    ?><div class="wrap">
        <form method="post">
            <?php 
            if( function_exists( 'true_image_uploader_field' ) ) {

                true_image_uploader_field('uploader_custom', get_option('uploader_custom') );
                true_image_uploader_field('uploader_custom_2', get_option('uploader_custom_2') );
            }
            ?><p class="submit">
                <input name="save" type="submit" class="button-primary" value="Сохранить изменения" />
                <input type="hidden" name="action" value="save" />
            </p>
        </form>
 
    </div><?php
}
 
add_action('admin_menu', 'true_add_options_page_u');
?> 
