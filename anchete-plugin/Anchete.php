<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Anchete_List_Table extends WP_List_Table {

	public static $per_page=10;

    public function __construct(){
        parent::__construct( array(
            'singular'  => 'anchete',    
            'plural'    => 'anchete-list',   
            'ajax'      => false      
        ) );
    }

    public function column_default($item, $column_name){
        $publish="selected";
        $unpublish="";
        $search_g;
        if($column_name=='yn'){
            $search_g= '<select name=person_in_charge id=person_in_charge_'.$item['ID'].' class=person_in_charge>';
            if($item[$column_name]!=1){
                $publish="";
                $unpublish="selected";
            }
            $search_g.= '<option value='.$item['ID'].'_1 '.$publish.'>公開</option>';
            $search_g.= '<option value='.$item['ID'].'_0 '.$unpublish.'>非公開</option>';
            $search_g.= '</select> <span id="complete'.$item['ID'].'"></span>';
        }


        $anchete_html;
        if($column_name=='anchetes'){
            $items=unserialize($item['anchetes']);
            for($i=0;$i<count($items);$i++){
                $anchete_html.=$items[$i][1].":".$items[$i][2]."<br>";
            }            
        }
        
        switch($column_name){
            case 'title':
                return $item[$column_name];//=sprintf('<a href="?page=anchete_submenu2&id=%d">%s</a>',$item['ID'],$item['title']);
            case 'anchetes':
                return $item[$column_name]=$anchete_html;
            case 'ID':
               return $item[$column_name]='[anchete id='.$item['ID'].']';
            case 'yn':
                return $item[$column_name]=$search_g;
            case 'delete':
                return $item[$column_name]=sprintf('<button class="delete_anchete" id=%d>%s</button>',$item['ID'],"削除");
            default:
                return print_r($item,true); 
        }
    }
    
    public function get_columns(){
        $columns = array(
            'title'  => 'アンケートタイトル',
            'anchetes'  => 'アンケート回答項目：回答数',
            'ID'    => 'タグ',
            'yn'    => '公開／非公開',
            'delete'    => '削除'
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'ID'     => array('ID',false) 
        );
        return $sortable_columns;
    }
    
   public function prepare_items() {
        global $wpdb; 

        $sql =  $wpdb->prepare('select * from '.$wpdb->prefix.'anchetes');
        $data = $wpdb->get_results($sql , ARRAY_A);

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
         
        function usort_reorder($a,$b){
            $orderby = (!empty(sanitize_text_field($_REQUEST['orderby']))) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
            $order = (!empty(sanitize_text_field($_REQUEST['orderby']))) ? sanitize_text_field($_REQUEST['orderby']) : 'desc';//
            $result = strnatcmp($a[$orderby], $b[$orderby]); //
            return ($order==='asc') ? $result : -$result; //
        }
        usort($data, 'usort_reorder');
 		
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*Self::$per_page),Self::$per_page);
        $this->items = $data;
        
        $this->set_pagination_args( array(
             'total_items' => $total_items, 
             'per_page'    => Self::$per_page, 
             'total_pages' => ceil($total_items/Self::$per_page)  
        ) );
    }

}
