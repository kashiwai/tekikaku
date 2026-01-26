
	$(document).ready(function(){
		buildIcons(machineList);
	});

	function buildIcons( obj ){
		
		var tags = obj.map(function(rec,idx){
			var idname = 'm_'+idx;
			var tag = $('<li>',{
			})
				.append(
					$('<div>',{
						class: 'iconheader',
						text: rec['model_name']
					})
				)
				.append(
					$('<div>',{
						class: 'icondetail',
					})
						.css('background-image', 'url(../../img/base/'+rec['image']+')')
						.append(
							$('<input>',{
								type: 'checkbox',
								id:   idname,
								class: 'machine_checkbox',
								name: idname,
								value: rec['machine_no']
							})
						)
						.append(
							$('<label>',{
								for:  idname,
								class: 'status_'+rec['status'],
								text: ''
							})
						)
				)
			;
			return tag;
		});

		$('#machineList')
			.empty()
			.append( tags )
		;
	}
