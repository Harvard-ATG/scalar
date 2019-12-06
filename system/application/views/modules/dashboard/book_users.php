<?if (!defined('BASEPATH')) exit('No direct script access allowed')?>
<?$this->template->add_js('system/application/views/widgets/tablesorter/jquery.tablesorter.min.js')?>
<?$this->template->add_css('system/application/views/widgets/tablesorter/style.css')?>

		<script>
		$(document).ready(function() {
			$(".tablesorter").tablesorter({
        		headers: {
        			0: {sorter: false },
            		8: {sorter: false }
        		}
   			});

   			$(window).on('resize', function() { resizeList(); });
   			resizeList();

		});

		function resizeList() {
    		$('.table_wrapper').height(Math.max(200, $(window).height() - ($('.table_wrapper').offset().top + 72))+'px'); // magic number to get list height right
		}

		function remove_book_user(book_id, user_id) {
			if ('undefined'==typeof(book_id) || book_id==0) return alert('Please select a book');
			if ('undefined'==typeof(user_id) || user_id==0) return alert('Invalid user ID');
			$.get('api/delete_book_user', {user_id:user_id, book_id:book_id}, function() {
				window.location.reload();
			});
		}

		function debounce(func, wait, immediate) {
	        let timeout;
	        return function() {
		        let context = this, args = arguments;
		        const later = function() {
			        timeout = null;
			        if (!immediate) func.apply(context, args);
		        };
		        const callNow = immediate && !timeout;
		        clearTimeout(timeout);
		        timeout = setTimeout(later, wait);
		        if (callNow) func.apply(context, args);
	        };
        };
		
		function request_book_user(book_id) {
				var $div = $('<div class="select_box"><h4 class="dialog_title">Add a user</h4>To connect a user to your book, first search for them by their full name.<br clear="both" /><br /><form><input class="generic_text_input" style="float:left;" type="text" name="fullname" value="Full name" /><input class="generic_button" style="float:left; margin-left:8px;" type="submit" value="Search" /><br clear="both" /></form><div class="results" style="padding-top:16px;padding-bottom:10px;"></div><a class="generic_button large" href="javascript:;" onclick="$(this).parent().remove();" style="float:right;font-size:larger;">Cancel</a></div>');
				$div.find('input:first').on('focus', function() {if ($(this).val() == 'Full name') $(this).val('');});
				$('body').append($div);
				$div.find('form:first').on('submit', function() {
					if ($div.find('input:first').val()=='Full name') return false;
					$div.find('input:submit').attr("disabled", "disabled");
					var fullname = this.fullname.value;
					$.get('api/user_search', {fullname:fullname}, function(data) {
						if (!data.length) {
							$div.find('.results').html('No users were found with the provided full name');
							$div.find('input:submit').prop("disabled", false);
							return false;
						}
						$div.find('.results').html('<div style="padding-bottom:10px;">Please select a user below to link them to your book:</div>');
						for (var j = 0; j < data.length; j++) {
							var $link = $('<a href="javascript:void(null);">'+data[j].fullname+'</a>');
							$link.data('user_id', data[j].user_id);
							var $row = $('<div></div>');
							$div.find('.results').append($row);
							$row.append($link);
						}
						$div.find('input:submit').prop("disabled", false);
						$div.find('a').on('click', function() {
							var user_id = parseInt($(this).data('user_id'));
							$.get('api/save_user_books', {id:user_id, selected_ids:[book_id], list_in_index:0}, function() {
								window.location.reload();
							});
							return false;
						});
					});
					return false;
				});
				$div.css('left', ((parseInt($(window).width())/2) - ($div.width()/2) + 'px') );
				$div.css('top', ((parseInt($(window).height())/2) - ($div.height()/2) + parseInt($(window).scrollTop()) + 'px') );
				$div.show();

		}

		function add_book_users(book_id) {
		  let bookUserIDs = Array.prototype.map
			  .call(document.querySelectorAll("[property='id']"), row => row.innerHTML);
	      const showEmail = <?php echo $this->config->item('show_email_in_search') ? 'true' : 'false'; ?>;

		  let $div = $('<div class="select_box add_book_user_select_box"><h4 class="dialog_title">Add a user</h4>To connect a user to your book, first search for them by their name or email.<br clear="both" /><br /><form><input class="generic_text_input" style="float:left;" type="text" name="searchValue" value="" /><br clear="both" /></form><div class="results" style="padding-top:16px;padding-bottom:10px;"></div><a class="generic_button large" href="javascript:;" onclick="window.location.reload();" style="float:right;font-size:larger;">Close</a></div>');
		  $('body').append($div);

			$(document).on('mouseup', function (e) { // if the user clicks away from the dialog, reload the page.
                if ($(e.target).parents(".add_book_user_select_box").length === 0 && !($(e.target).hasClass("add_book_user_select_box"))) {
                    window.location.reload();
                }
            });

			$div.find('.generic_text_input').on('keyup', debounce(function() {
				let sq = this.value;
				if (sq.trim() !== "" && sq.trim().length > 1) { // dont search on empty, nor just spaces, nor anything less than 2 chars
					$.get('api/get_system_users_by_name_or_email', {sq:sq}, function(data) {
						if (!data.length) {
							$div.find('.results').html('No users were found.');
							return false;
						}
						$div.find('.results').html('<div style="padding-bottom:10px;">Please select a user below to link them to your book:</div>');
						data.forEach( (u)=> {
							let row = $('<div style="padding-bottom:.68em;"></div>');
							let link = $('<a href="javascript:void(null);">'+u.fullname+'</a>');
							row.append(link);
							link.data('user_id', u.user_id);
							if (showEmail) {
								let email = $('<span>  --  '+u.email+'</span>');
								row.append(email);
							}
							$div.find('.results').append(row);
							row.append("<span class='animator'></span>");
							if (bookUserIDs.indexOf(u.user_id) >= 0) {
								row.find('.animator').addClass('check');
							}
						});
						$div.find('a').on('click', function() {
							let user_id = parseInt($(this).data('user_id'));
							let item = $(this);
							 if (!item.hasClass('generic_button') && bookUserIDs.indexOf(String(user_id)) < 0){
								item.parent().find('.animator').addClass('spinner');
								$.get('api/save_user_books', {id:user_id, selected_ids:[book_id], list_in_index:0}, function() {
									setTimeout(function() {
										item.parent().find('.animator').removeClass('spinner');
										item.parent().find('.animator').addClass('check');
										bookUserIDs.push(String(user_id));
									}, 500);
								});
							}
							return false;
						});
					});
				}
				else if ($div.find('.results').length > 0) {
					 $div.find('.results').html('');
				}
				return false;
			}, 350));

			$div.css('left', ((parseInt($(window).width())/2) - ($div.width()/2) + 'px') );
			$div.css('top', ((parseInt($(window).height())/2) - ($div.height()/2) + parseInt($(window).scrollTop()) + 'px') );
			$div.show();
		}

		function user_get_contributions(user_id, the_link) {
			var $the_link = $(the_link);
			var book_id = $('select[name="book_id"] :selected').val();
			if ('undefined'==typeof(book_id)||0==book_id) {  // This is for super admins, who may not have this book selected
				if (document.location.search.indexOf('book_id=')!=-1) {
					var book_id = document.location.search.substr( document.location.search.indexOf('book_id=')+8 );
					if (book_id.indexOf('&')!=0) book_id = book_id.substr(0, book_id.indexOf('&'));
					book_id = parseInt(book_id);
				}
			};
			if ('undefined'==typeof(book_id)||0==book_id) {
				alert('Could not determine book ID');
				return false;
			};
			// Get contributions
			if (!$the_link.data('is_open')) {
				$the_link.trigger('blur');
				$the_link.html('Loading...');
				$the_link.data('is_open',true);
				var $the_row = $('#user_row_'+user_id)
				$.get('api/get_user_contributions', {book_id:book_id,user_id:user_id}, function(data) {
					var $next = $the_link.parent().parent().next();
					if ($next.hasClass('version_wrapper')) $next.remove();
					if (data.length == 0) {
						$the_row.after('<tr class="version_wrapper"><td>&nbsp;</td><td class="odd" colspan="8">No contributions found</td></tr>');
					} else {
					   	var $row = $('<tr class="version_wrapper"><td colspan="9" style="padding:0px 0px 0px 0px;"><table style="width:100%;" cellspacing="0" cellpadding="0"></table></td></tr>');
					   	var $header = ('<tr><th>ID</th><th>Version</th><th>Title</th><th>Description</th><th>Content</th><th>User</th><th>Created</th></tr>');
					   	$row.find('table').html($header);
					   	$the_row.after($row);
					    for (var j in data) {
					    	var $page_row = $('<tr class="header" id="content_row_'+data[j].content_id+'" typeof="pages"><td colspan="7"><a href="<?=confirm_slash(base_url()).@$book->slug?>/'+data[j].slug+'">'+data[j].slug+'</a></td></tr>');
					    	$row.find('table').find('tr:last').after($page_row);
					    	for (var k in data[j].versions) {
						    	var $version_row = $('<tr class="'+((user_id==data[j].versions[k].user)?'hl':'')+'" id="version_row_'+data[j].versions[k].version_id+'" typeof="versions"></tr>');
								$version_row.append('<td property="id">'+data[j].versions[k].version_id+"</td>");
								$version_row.append('<td property="version_num">'+data[j].versions[k].version_num+"</td>");
								$version_row.append('<td property="title"><a href="<?=confirm_slash(base_url()).@$book->slug?>/'+data[j].slug+'.'+data[j].versions[k].version_num+'">'+data[j].versions[k].title+"</a></td>");
								$version_row.append('<td property="description"><span class="full">'+data[j].versions[k].description+'</span><span class="clip">'+create_excerpt(data[j].versions[k].description,8)+'</span></td>');
								$version_row.append('<td property="content"><span class="full">'+data[j].versions[k].content+'</span><span class="clip">'+create_excerpt(data[j].versions[k].content,8)+'</span></td>');
								$version_row.append('<td property="user">'+data[j].versions[k].user+"</td>");
								$version_row.append('<td property="created" style="white-space:nowrap;">'+data[j].versions[k].created+"</td>");
								$row.find('table').find('tr:last').after($version_row);
					    	}
					    }
					}
					$the_link.html('Hide');
				});
			// Remove versions
			} else {
				var $next = $the_link.parent().parent().next();
				if ($next.hasClass('version_wrapper')) $next.remove();
				$the_link.data('is_open',false);
				$the_link.trigger('blur');
				$the_link.html('View');
			}

		}
		</script>
<?
	if (empty($book)):
		echo 'Please select a book to manage using the pulldown menu above';
	else:
?>
		<div class="table_wrapper">
		<table cellspacing="0" cellpadding="0" style="width:100%;" class="tablesorter" id="user-table">
		<thead>
			<tr class="head">
				<th></th>
				<th>ID</th>
				<th>Relationship</th>
				<th>In index</th>
				<th>Order</th>
				<th>Full name</th>
				<th>Email</th>
				<th>URL</th>
				<th>Contributions</th>
			</tr>
		</thead>
		<tbody>
<?
			$count = 1;
			foreach ($current_book_users as $row) {
				echo '<tr typeof="user_books" id="user_row_'.$row->user_id.'">';
				echo '<td style="white-space:nowrap;"><a href="javascript:;" onclick="edit_row($(this).parents(\'tr\'));" class="generic_button">Edit</a> <a href="javascript:remove_book_user('.@$book->book_id.','.$row->user_id.');" style="color: rgb(136, 136, 136);" onclick="return confirm(\'Are you sure you wish to REMOVE the connection to '.htmlspecialchars($row->fullname,ENT_QUOTES).'?\');" class="generic_button">Remove</a></td>'."\n";
				echo '<td property="id">'.$row->user_id."</td>\n";
				echo '<td class="editable enum {\''.implode("','",$relationships).'\'}" property="relationship">'.$row->relationship."</td>\n";
				echo '<td class="editable boolean" property="list_in_index">'.$row->list_in_index."</td>\n";
				echo '<td class="editable number" property="sort_number">'.$row->sort_number."</td>\n";
				echo '<td property="fullname">'.$row->fullname."</td>\n";
				echo '<td property="email">'.$row->email."</td>\n";
				echo '<td property="url">'.((!empty($row->url))?'<a href="'.$row->url.'" target="_blank">':'').$row->url.((!empty($row->url))?'</a>':'')."</td>\n";
				echo '<td style="white-space:nowrap;text-align:center;"><a href="javascript:;" class="generic_button" onclick="user_get_contributions('.$row->user_id.',this);">View</a></td>'."\n";
				echo "</tr>\n";
				$count++;
			}
?>
			</tr>
		</tbody>
		</table>
		</div>
		<div style="padding:20px 0 6px 0">
			<? if (!empty($book) && $login_is_super): ?>
			<a class="generic_button large" title="Super admin feature" href="javascript:void(null);" onclick="add_book_users(<?=$book->book_id?>);" id="connect_book_user_link">Add a user (admin)</a>
			<? endif ?>
			<a class="generic_button large" href="javascript:void(null);" onclick="request_book_user(<?=$book->book_id?>)" id="request_book_user_link">Add a user</a>
		</div>
<? endif ?>
