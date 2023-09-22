jQuery(document).on('click','.anchete_btn',function(){
    var target = jQuery(this);
    var val = target.attr('id').split('_');
    target.prevAll().attr('class', 'anchete_btn_done')
    target.attr('class', 'anchete_btn_done')
    target.nextAll().attr('class', 'anchete_btn_done')
    //console.log(ajaxurl)
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            'action' : 'anchete_count_up',
            'id' : val[1],
            'num' : val[2]
        },
        success: function(response) {
            res = JSON.parse( response );
            //console.log(res[0]);
            for(let i=0;i<res[1].length;i++){
                // jQuery('#progressbar_'+val[1]+"_"+i).css('width',Math.floor(res[1][i][2]/res[0]*100)+'%');
                // jQuery('#progressbar_'+val[1]+"_"+i).html(Math.floor(res[1][i][2]/res[0]*100)+'%');
                jQuery('#anchete_'+val[1]+"_"+i).html(res[1][i][1]+"｜"+Math.floor(res[1][i][2]/res[0]*100)+'%');
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){

        }
    });
});

/*公開非公開*/
jQuery('.person_in_charge').on('change',function(){
    var val=jQuery(this).val();
    var array_id=val.split("_");
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            'action' : 'anchete_publish_edit',
            'ID' : array_id[0],
            'yn' : array_id[1]
        },
        success: function( response ){
            res = JSON.parse( response );
            jQuery('#complete'+array_id[0]).html(res); 
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
            
        }
    });
});

/*削除*/
jQuery('.delete_anchete').on('click',function(){
    var idStr = jQuery(this).attr('id');

    if(window.confirm('本当に削除しますか？')){
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                'action' : 'anchete_delete',
                'ID' : idStr
            },
            success: function( response ){
                location.href="?page=anchete_menu"    
            },
            error: function(XMLHttpRequest, textStatus, errorThrown){
    
            }
        });
	}else{
        return false; 
	}
});