$(document).ready(function(){ $('.fancybox').fancybox(); });

$(document).ready(function(){
	$(".qx_del").click(function() {
	  if(!confirm('Вы подтверждаете удаление этого изображения?')){return false;}
	});
});