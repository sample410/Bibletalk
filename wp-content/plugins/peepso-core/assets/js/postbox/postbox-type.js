import $ from 'jquery';
import { observer } from 'peepso';

class PostboxType {
	/**
	 * Initialize postbox type selector dropdown.
	 *
	 * @param {JQuery} $postbox
	 */
	constructor( $postbox ) {
		this.$postbox = $postbox;
		this.$container = this.$postbox.find( '#type-tab' );
		this.$toggle = this.$container.find( '.interaction-icon-wrapper' );
		this.$dropdown = this.$postbox.find( '.ps-js-postbox-type' );

		this.defaultIcon = this.$toggle
			.children( 'a' )
			.find( 'i' )
			.attr( 'class' );
		this.defaultText = this.$toggle
			.children( 'a' )
			.find( 'span' )
			.html();

		this.$toggle.on( 'click', () => this.toggle() );

		this.$dropdown.on( 'click', '[data-option-value]', e => {
			let type = $( e.currentTarget ).data( 'optionValue' );

			e.stopPropagation();
			this.select( type );
			this.hide();
		} );

		this.$postbox.on( 'postbox.post_saved', () => {
			let customHandler = observer.applyFilters( 'peepso_postbox_onsave', false, this.$postbox );
			if ( 'function' !== typeof customHandler ) {
				this.select( 'status' );
			}
		} );

		this.select( 'status' );
	}

	/**
	 * Toggle dropdown.
	 */
	toggle() {
		if ( this.$dropdown.is( ':hidden' ) ) {
			this.show();
		} else {
			this.hide();
		}
	}

	/**
	 * Show dropdown.
	 */
	show() {
		this.$dropdown.show();

		// Add autohide on document-click.
		setTimeout( () => {
			let evtName = 'click.ps-postbox-type';
			$( document )
				.off( evtName )
				.one( evtName, () => this.hide() );
		}, 1 );
	}

	/**
	 * Hide dropdown.
	 */
	hide() {
		this.$dropdown.hide();

		// Remove autohide on document-click.
		$( document ).off( 'click.ps-postbox-type' );
	}

	/**
	 * Change the post type.
	 *
	 * @param {string} type
	 */
	select( type ) {
		let $source = this.$dropdown.find( `[data-option-value="${ type }"]` ),
			$target = this.$toggle.children( 'a' ),
			$sourceIcon = $source.find( 'i' ),
			$targetIcon = $target.find( 'i' ).filter( `.${ $sourceIcon.attr( 'class' ) }` );

		if ( $source.length ) {
			// Update active item.
			$source.addClass( 'active' );
			$source.siblings( '.active' ).removeClass( 'active' );
			$targetIcon.addClass( 'active' );
			$targetIcon.siblings( '.active' ).removeClass( 'active' );

			// Trigger related action hooks.
			observer.doAction( 'postbox_type_set', this.$postbox, type );
		}
	}
}

// Postbox action hook on the main postbox.
observer.addAction(
	'peepso_postbox_addons',
	addons => {
		let wrapper = {
			init() {},
			set_postbox( $postbox ) {
				if ( $postbox.find( '#type-tab' ).length ) {
					new PostboxType( $postbox );
				}
			}
		};
		addons.push( wrapper );
		return addons;
	},
	10,
	1
);

// Postbox action hook on edit post.
observer.addAction(
	'postbox_init',
	postbox => {
		// Users should not be able to edit post type when editing a post.
		let $postbox = postbox.$el;
		$postbox.find( '#type-tab' ).remove();
	},
	10,
	1
);
