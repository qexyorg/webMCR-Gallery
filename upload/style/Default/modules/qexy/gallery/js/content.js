$(function(){

	$('body').on('click', '.lightbox li > a', function(){

		var lb_id = $(this).closest('.lightbox').attr('id');

		var lb_img_id = $(this).attr('href');

		var img_url = $(this).children().attr('src');

		var title = $(this).attr('title');
		var text = $(this).attr('data-text');

		$('.lightbox').append('<div class="lb-img-box" data-id="'+lb_img_id+'">'+
							'<div class="lb-img-body">'+
								'<img src="' + img_url + '" alt="IMG">'+
								'<a href="' + img_url + '" class="lb-original" title="Открыть оригинал">'+
									'<i class="icon-resize-full"></i>'+
								'</a>'+
								'<a href="#" class="lb-close" title="Закрыть">'+
									'<i class="icon-remove"></i>'+
								'</a>'+
								'<div class="lb-title">'+title+'</div>'+
								'<div class="lb-text">' + text+'</div>'+
								'<a href="#" class="lb-control-left"><</a>'+
								'<a href="#" class="lb-control-right">></a>'+
							'</div>'+
						'</div>');

		return false;
	});

	$('body').on('click', '.lb-img-box', function(e){

		if(e.target.closest('.lb-img-body')===null){

			$(this).closest('.lb-img-box').fadeOut(function(){
				$(this).remove();
			});

			return false;
		}

	});

	$('body').on('click', '.lightbox .lb-close', function(){
		
		$(this).closest('.lb-img-box').fadeOut(function(){
			$(this).remove();
		});

		return false;
	});

	$('body').on('click', '.lb-img-box .lb-control-left, .lb-img-box .lb-control-right', function(){

		var lb_this_id = $(this).closest('.lb-img-box').attr('data-id');

		if($(this).hasClass('lb-control-left')){
			var lb_prev = $('.lightbox li > a[href="'+lb_this_id+'"]').parent().prev('li');
		}else{
			var lb_prev = $('.lightbox li > a[href="'+lb_this_id+'"]').parent().next('li');
		}
		

		if(lb_prev[0]===undefined){ return false; }

		var lb_prev_id = lb_prev.children('a').attr('href');
		var lb_prev_title = lb_prev.children('a').attr('title');
		var lb_prev_text = lb_prev.children('a').attr('data-text');

		var lb_prev_url = lb_prev.find('img').attr('src');

		$('.lb-img-box > .lb-img-body > img').attr('src', lb_prev_url);
		$('.lb-img-box > .lb-img-body > .lb-original').attr('href', lb_prev_url);
		$('.lb-img-box > .lb-img-body > .lb-title').html(lb_prev_title);
		$('.lb-img-box > .lb-img-body > .lb-text').html(lb_prev_text);
		$('.lb-img-box').attr('data-id', lb_prev_id);

		return false;
	});

	$('body').on('click', '.cb_selector', function(){

		var selector = $(this).attr('data-select');

		var prop = ($(this)[0].checked) ? true : false;
		$('input[type="checkbox"].'+selector).prop('checked', prop);

	});

	$('body').on('click', '.submit_act', function(){

		var select_id = $($(this).attr('data-act'));

		var checkeds = $('input.'+$(this).attr('data-input')+':checked');

		var action = select_id.val();

		var url = select_id.attr('data-url')+action;

		switch(action){
			case 'add': location.href = url; break;
			case 'edit':
				if(checkeds.length!=1){ alert('Редактировать можно только один элемент'); return false; }

				location.href = url+'&iid='+checkeds.val();
			break;

			case 'delete':
				if(checkeds.length<=0){ alert('Вы не выбрали ни один элемент'); return false; }

				if(!confirm('Вы уверены, что хотите удалить выбранные элементы?')){ return false; }

				$(this).closest('form').attr('action', url);

				return true;
			break;

			default: return false; break;
		}

		return false;
	});
});