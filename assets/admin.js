(function( $, _ ){
	
	window.simple_todo_list = {
		
		el      : {},

		working : false,

		responses : {

			add_item : function( response ) {
				if ( ! _.isEmpty( response ) && true == response.success ) {
					template = _.template( obj.el.template.html(), null, { variable: 'data' } );

					$( template({
						'post_id'        : response.data.post_id,
						'post_content'   : response.data.post_content,
						'time_diff'      : response.data.time_diff,
						'complete_nonce' : response.data.complete_nonce,
						'do_item_nonce'  : response.data.do_item_nonce,
						'delete_nonce'   : response.data.delete_nonce,
						'delete_string'  : response.data.delete_string
					} ) ).hide().prependTo( obj.el.list_table ).fadeIn( 400 )
				}
			},

			clear_list : function( response ) {
				if ( ! _.isEmpty( response ) && true == response.success ) {
					obj.clear_list()
				}
			}
		},

		triggers : {

			toggle_add_form : function( a, form_should_be_visible ) {
				if ( form_should_be_visible ) {
					obj.el.add_item_form.show()
					obj.el.cancel_add_item.show()
					obj.el.add_item.hide()
				} else {
					obj.el.widget.find( 'input#simple-todo-list-new-item-content' ).val( '' )
					obj.el.add_item_form.hide()
					obj.el.cancel_add_item.hide()
					obj.el.add_item.show()
				}
			}
		},

		clear_list : function() {
			$.each( obj.el.list_table.find( 'tr' ), function(i, el) {
				$(el).remove()
			}) 
		},

		do_item: function(e) {
			e.preventDefault()

			$this = $(this),
			tr    = $this.parent().parent( 'tr' ),
			data  = {
				post_id : tr.data( 'todo-item-id' ),
				nonce   : $this.data( 'wp-nonce' )
			};

			if ( tr.hasClass( 'simple-todo-list-is-done' ) ) {
				data.action = 'simple_todo_list_undo_item';

				tr.removeClass( 'simple-todo-list-is-done' )

				$.post( ajaxurl, data, function( response ) {
					if ( _.isEmpty( response ) || true !== response.success ) {
						tr.addClass( 'simple-todo-list-is-done' )
					}
				})

			} else {
				data.action = 'simple_todo_list_do_item';
				tr.addClass( 'simple-todo-list-is-done' )
								
				$.post( ajaxurl, data, function( response ) {
					if ( _.isEmpty( response ) || true !== response.success ) {
						tr.removeClass( 'simple-todo-list-is-done' )
					}
				})
			}
		},

		do_ajax : function(e) {
			e.preventDefault()

			el     = $(this),
			action = el.data( 'todo-action' ),
			data   = {
				action  : 'simple_todo_list_' + action,
				nonce   : el.data( 'wp-nonce' ),
				post_id : el.parents( 'tr' ).first().data( 'todo-item-id' ),
				content : obj.get_content( el )
			};

			if ( 'clear_list' == action ) {
				confirmed = confirm( simple_todo_list_i18n.clear_list );
				if ( false == confirmed ) {
					return false;
				}

				data.clear_ids = [];
				obj.el.widget.find( 'tr' ).each( function(i, el) {
					data.clear_ids.push( $(el).data( 'todo-item-id' ) )
				})
			}

			if ( 'delete_item' == action ) {
				deleted = obj.el.widget.find( 'tr[data-todo-item-id="' + data.post_id + '"]' );
				deleted.remove()
			}

			obj.set_ajax( true, action )

			$.post( ajaxurl, data, function( response ) {

				obj.toggle_add_form( null, action )

				switch ( action ) {
					case 'add_item' :
						obj.responses.add_item( response )
						break;
					case 'clear_list' :
						obj.responses.clear_list( response )
						break;
				}

				obj.set_ajax( false, action )
			})
			
			return false;
		},

		get_content : function( el ) {
			if ( 'add_item' == el.data( 'todo-action' ) ) {
				return this.el.widget.find( '.simple-todo-list-add-item-form form' ).serializeArray();
			}
			return '';
		},
		
		init : function() {
			this.set_els()
			
			this.el.widget.on( 'click', 'a.simple-todo-list-action', this.do_ajax )
			this.el.widget.on( 'click', '.simple-todo-list-do-item', this.do_item )
			this.el.widget.on( 'simple-todo-list-new-item-toggle', this.triggers.toggle_add_form )

			this.el.add_item.on( 'click', this.toggle_add_form )
			this.el.cancel_add_item.on( 'click', this.toggle_add_form )
		},

		set_ajax : function( working, action ) {

			if ( 'delete_item' == action ) return;

			if ( false == working ) {
				this.working = false;
				this.el.widget.find( '.simple-todo-list-wrap' ).removeClass( 'simple-todo-list-ajax-working' )
			} else {
				this.working = true;
				this.el.widget.find( '.simple-todo-list-wrap' ).addClass( 'simple-todo-list-ajax-working' )
			}

			obj.el.widget.trigger( 'simple-todo-list-new-item-toggle', [ false ] )
		},
		
		set_els : function() {
			this.el.widget          = $( '#dashboard-widgets-wrap' ).find( '#simple_todo_list' );
			this.el.add_item        = this.el.widget.find( 'a.simple-todo-list-add-item-toggle' );
			this.el.add_item_form   = this.el.widget.find( '.simple-todo-list-add-item-form' );
			this.el.cancel_add_item = this.el.widget.find( 'a.simple-todo-list-cancel-add-item' );
			this.el.list_table      = this.el.widget.find( '.simple-todo-list .form-table' );
			this.el.template        = this.el.widget.find( '#simple-todo-list-single-todo' );
		},

		toggle_add_form : function( e, action ) {

			if ( 'delete_item' == action ) return; 

			if ( null !== e ) {
				e.preventDefault()
			}

			obj.el.add_item.toggle()
			obj.el.cancel_add_item.toggle()
			obj.el.widget.trigger( 'simple-todo-list-new-item-toggle', [ $( 'a.simple-todo-list-cancel-add-item:visible' ).length ] )
		}
	};

	obj = window.simple_todo_list;

	window.simple_todo_list.init()

})( window.jQuery, window._ );