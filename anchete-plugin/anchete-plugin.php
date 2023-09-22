<?php
/*
Plugin Name: Easy Anchete 
Plugin URI: 
Description: 記事中に簡単なアンケートを実施できます。
Version: 1.0.0
Author: marbou
Author URI: https://marbou-work.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit;

//=================================================
// プラグイン有効化時に実行
//=================================================
function anchte_plugin_init(){
	global $wpdb; 
	$wpdb->query( $wpdb->prepare('
	CREATE TABLE if not exists '.$wpdb->prefix.'anchetes (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`title` varchar(50) NOT NULL,
		`anchetes` text NOT NULL,
		`count` int(11) NOT NULL,
		`yn` tinyint(4) NOT NULL,
		PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
	), ARRAY_A);
}
register_activation_hook(__FILE__, 'anchte_plugin_init');

//=================================================
// プラグイン削除時に実行
//=================================================
function anchte_plugin_delete()
{
	global $wpdb;
	$wpdb->query( $wpdb->prepare('DROP TABLE IF EXISTS '.$wpdb->prefix.'anchetes', ARRAY_A));
}
register_uninstall_hook ( __FILE__, 'anchte_plugin_delete' );

//=================================================
// 管理画面に「メニュー」を追加登録
//=================================================
add_action('admin_menu', function(){
	//---------------------------------
	// メインメニュー① ※実際のページ表示はサブメニュー①
	//---------------------------------	
    add_menu_page(
		'アンケート管理' // ページのタイトルタグ<title>に表示されるテキスト
		, 'アンケート管理'   // 左メニューとして表示されるテキスト
		, 'manage_options'       // 必要な権限 manage_options は通常 administrator のみに与えられた権限
		, 'anchete_menu'        // 左メニューのスラッグ名 →URLのパラメータに使われる /wp-admin/admin.php?page=anchete_menu
		, '' // メニューページを表示する際に実行される関数(サブメニュー①の処理をする時はこの値は空にする)
		, 'dashicons-admin-tools'       // メニューのアイコンを指定 https://developer.wordpress.org/resource/dashicons/#awards
		, 81                            // メニューが表示される位置のインデックス(0が先頭) 5=投稿,10=メディア,20=固定ページ,25=コメント,60=テーマ,65=プラグイン,70=ユーザー,75=ツール,80=設定
	);

	//---------------------------------
	// サブメニュー① ※事実上の親メニュー
	//---------------------------------
	add_submenu_page(
		'anchete_menu'    // 親メニューのスラッグ
		, 'アンケート管理' // ページのタイトルタグ<title>に表示されるテキスト
		, 'アンケート管理' // サブメニューとして表示されるテキスト
		, 'manage_options' // 必要な権限 manage_options は通常 administrator のみに与えられた権限
		, 'anchete_menu'  // サブメニューのスラッグ名。この名前を親メニューのスラッグと同じにすると親メニューを押したときにこのサブメニューを表示します。一般的にはこの形式を採用していることが多い。
		, 'anchete_submenu1_page_contents' //（任意）このページのコンテンツを出力するために呼び出される関数
		, 0
	);

	//---------------------------------
	// サブメニュー②
	//---------------------------------
	add_submenu_page(
		'anchete_menu'    // 親メニューのスラッグ
		, '新規登録' // ページのタイトルタグ<title>に表示されるテキスト
		, '新規登録' // サブメニューとして表示されるテキスト
		, 'manage_options' // 必要な権限 manage_options は通常 administrator のみに与えられた権限
		, 'anchete_add' //サブメニューのスラッグ名
		, 'anchete_add_page_contents' //（任意）このページのコンテンツを出力するために呼び出される関数
		, 1
	);
});
//=================================================
// アンケート管理
//=================================================
function anchete_submenu1_page_contents() {

	if(!class_exists('WP_List_Table')){
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	require_once( WP_PLUGIN_DIR . '/anchete-plugin/Anchete.php' );

	//wp_enqueue_script( 'jquery', '//code.jquery.com/jquery-3.5.1.min.js', "", "1.0.0", false );// jQueryの読み込み

	wp_enqueue_script(
		'custom_script',
		plugins_url( 'js/anchete.js?'.wp_date("YmdHis"), __FILE__ ),
	);	

	$anchete_tbl = new Anchete_List_Table();
	$anchete_tbl->prepare_items();

	$html='<div class="wrap"> ';
	$html.='<h2>アンケート管理 <a href="?page=anchete_add" class="page-title-action">新規登録</a></h2>';
	$html.='<hr>';
	$html.='<div>';
	$html.='<div>タグ（[anchete id=xx]）をコピーし記事に貼り付けて下さい。</div>';
	$html.='<form id="data-filter" method="">';
	echo $html;
    $html.=$anchete_tbl->display();
	$html='</form>';
    $html.='</div>';
	$html.='</div>';
	echo $html;
}

//=================================================
// アンケート登録
//=================================================
function anchete_add_page_contents() {
	global $wpdb; 

	wp_enqueue_style( 'jquery-ui-css', 
		plugins_url( 'css/style.css?as', __FILE__ ),
		"", "1.0.0", false 
	);

	//---------------------------------
    // ユーザーが必要な権限を持つか確認
	//---------------------------------
    if (!current_user_can('manage_options')){
      wp_die( __('この設定ページのアクセス権限がありません') );
    }

	$message_html = "";
	$title="";
	$anchetes="";
	$isInputCheck=true;
	$contents;
	$str_anchetes="";
	//---------------------------------
	// 更新されたときの処理
	//---------------------------------
	if($_SERVER["REQUEST_METHOD"] === "POST"){
		if ( !isset( $_POST['anchete_add_item'] ) ) {
			echo '認証できません1';
			exit;
		} 
		if ( !check_admin_referer( 'anchete_add_act', 'anchete_add_item' ) ) {
			echo '認証できません2';
			exit;
		} 

		if ( !empty($_POST["title"])){
			$title = str_replace(array(" ", "　"), "", esc_html($_POST[ 'title' ]));
		}else{
			$isInputCheck=false;
		}	

		if ( !empty($_POST["anchetes"])){
			$str_anchetes = str_replace(array("\r\n", "\r", "\n"), "\n", esc_html($_POST["anchetes"]));
			$arr_anchetes = explode("\n", $str_anchetes);
	
			$ary = array();
			for($i=0;$i<count($arr_anchetes);$i++){
				$item=str_replace(array(" ", "　"), "", $arr_anchetes[$i]);
				if(mb_strlen($item)>0){
					$stack = array($i, $item, 0);
					array_push($ary,$stack);	
				}else{
					;
				}
			}
			// $ary = [
			// 	[0,"いるか", 0],
			// 	[1,"シャチ", 0],
			// 	[2,"クジラ", 0],
			// 	[3,"カメ", 0]
			// ];
			$contents=serialize($ary);
		}else{
			$isInputCheck=false;
		}

		if($isInputCheck){

			$result=$wpdb->insert( $wpdb->prefix .'anchetes', 
			array( 
				'title' => $title , 
				'anchetes' => $contents, 
				'count' => 0 ,
				'yn' => true ),
				array('%s', '%s', '%d', '%s'
				)
			);
	
			// 画面にメッセージを表示
			$message_html = '<div class="notice notice-success is-dismissible">';
			$message_html .= '<p>アンケートを登録しました。';
			$message_html .= '<br><a href="?page=anchete_menu">アンケート管理</a>';
			$message_html .= 'よりタグをコピーし記事に貼り付けてください。</p></div>';

		}else{
			$message_html = '<div class="notice notice-error notice-alt is-dismissible">';
			$message_html .= '<p>';
			$message_html .= 'タイトルと回答項目は必須です';
			$message_html .= '</p></div>';
		}

	}
	$nonce=wp_nonce_field("anchete_add_act", "anchete_add_item");

	$html = $message_html;
	$html .= '<div class="wrap anchete-add-area">';
	$html .= '<h2>アンケート登録</h2>';
	$html .= '<hr>';
	$html .= '<form name="anchete-add-form" method="post" action="">';
	$html .= $nonce;
	$html .= '<div class="">';
	$html .= '<div>アンケートタイトルを入力してください</div>';
	$html .= 'Q.<input type="text" name="title" value="'.$title.'" style=""width:100% placeholder="例）好きな食べ物は何？" size="62">';
	$html .= '</div>';
	$html .='<div class="" style="margin-top:3%">';
	$html .='<div>アンケートの回答項目を入力してください</div>';
	$html .='<textarea name="anchetes" id="anchetes" cols="60" rows="10" placeholder="回答は1項目ごとに改行してください">'.$str_anchetes.'</textarea>';
	$html .='</div><hr />';
	$html .='<p class="submit">';
	$html .='<input type="submit" name="submit" class="button-primary" value="登録する" />';
	$html .='</p>';
	$html .='</form>';
	$html .='</div>';

	echo $html;

}

//=================================================
// ショートコード
//=================================================
function anchete_function($atts){
	wp_enqueue_style( 'jquery-ui-css', 
		plugins_url().'/anchete-plugin/css/style.css?'.wp_date("YmdHis"), 
		"", "1.0.0", false 
	);

	global $wpdb; 

	$query = $wpdb->prepare( "select * from ".$wpdb->prefix."anchetes where ID=%d && yn=1", $atts['id']);
	$data = $wpdb->get_results( $query , ARRAY_A);

	if(count($data)==0){
		return;
	}

	$title="";
	$ar="";
	$cnt=0;
	foreach( $data as $v ){	
		$title=$v['title'];
		$ar = unserialize($v['anchetes']);
		$cnt= $v['count'];
	}

	$anchete_html="<div class='anchete'>";
	$anchete_html.="<div class='anchete_title'>".$title."</div>";
	$anchete_html.="<div>";
		for($i=0;$i<count($ar);$i++){
		$anchete_html.="<span class='anchete_btn ' id='anchete_".$atts['id']."_".$ar[$i][0]."'>".$ar[$i][1]."</span>";
		//$anchete_html.='<div class="progress" id="progressbar_'.$atts['id']."_".$ar[$i][0].'" style="height: 20px;">'.$ar[$i][1].'</div>';
	}
	$anchete_html.="</div>";
	$anchete_html.="</div>";

	return $anchete_html ;
}
add_shortcode('anchete','anchete_function');

function anchete_scripts_method() {
    wp_enqueue_script( 'jquery', '//code.jquery.com/jquery-3.5.1.min.js', "", "1.0.0", false );// jQueryの読み込み
	wp_enqueue_script(
		'custom_script',
		plugins_url( 'js/anchete.js?'.wp_date("YmdHis"), __FILE__ ),
	);	
}
add_action('wp_enqueue_scripts', 'anchete_scripts_method');
function add_ajaxurl() {
?>
	<script>
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
	</script>
<?php
}
add_action( 'wp_footer', 'add_ajaxurl', 1 );

/***********************
 * 
 * 回答のカウント
 * 
 * 
************************/
function anchete_count_up(){
	global $wpdb; 
	$id = sanitize_text_field($_POST['id']);
	$num = sanitize_text_field($_POST['num']);

	$query = $wpdb->prepare( "select * from ".$wpdb->prefix ."anchetes where ID=%d", $id);
	$data = $wpdb->get_results( $query , ARRAY_A);

	$anchetes="";
	$count=0;
	foreach( $data as $v ){	
		$anchetes = unserialize($v['anchetes']);
		$count=$v['count'];
	}

	$anchetes[$num][2]++;
	$anchetes_s=serialize($anchetes);
	
	$wpdb->query( $wpdb->prepare("update ".$wpdb->prefix ."anchetes set anchetes=%s ,count=count+1 where ID =%d", $anchetes_s, $id), ARRAY_A);
	$response = [$count+1, $anchetes];

	echo wp_json_encode($response, JSON_UNESCAPED_UNICODE);

	die();
}
add_action( 'wp_ajax_anchete_count_up', 'anchete_count_up' );
add_action( 'wp_ajax_nopriv_anchete_count_up', 'anchete_count_up' );

/***********************
 * 
 * 公開／非公開の変更
 * 
 * 
************************/
function anchete_publish_edit()
{
	global $wpdb;

	$id = sanitize_text_field($_POST['ID']);
	$yn = sanitize_text_field($_POST['yn']);

	$wpdb->query( $wpdb->prepare("update ".$wpdb->prefix ."anchetes set yn=%d where ID =%d", $yn, $id), ARRAY_A);
	$response = "変更完了";

	echo wp_json_encode($response, JSON_UNESCAPED_UNICODE);

	die();
}
add_action( 'wp_ajax_anchete_publish_edit', 'anchete_publish_edit' );
add_action( 'wp_ajax_nopriv_anchete_publish_edit', 'anchete_publish_edit' );

/***********************
 * 
 * 削除
 * 
 * 
************************/
function anchete_delete()
{
	global $wpdb;

	$id = sanitize_text_field($_POST['ID']);

	$wpdb->query( $wpdb->prepare("delete from ".$wpdb->prefix ."anchetes where ID =%d", $id), ARRAY_A);
	$response = "OK";

	echo wp_json_encode($response, JSON_UNESCAPED_UNICODE);

	die();
}
add_action( 'wp_ajax_anchete_delete', 'anchete_delete' );
add_action( 'wp_ajax_nopriv_anchete_delete', 'anchete_delete' );