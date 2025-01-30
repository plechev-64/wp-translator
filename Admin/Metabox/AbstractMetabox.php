<?php

namespace Src\Module\Translator\Admin\Metabox;

abstract class AbstractMetabox {
	private PropsMetabox $props;
	public bool $isTerm = false;
	public bool $isPost = false;

	abstract function getProps(): PropsMetabox;

	abstract function getContent( object $item ): string;

	abstract function update( int $itemId ): void;

	public function init(): void {

		$this->props = $this->getProps();

		if ( $this->props->postTypes ) {
			if ( ! $this->props->postIds || $this->props->postIds && isset( $_GET['action'] ) && isset( $_GET['post'] ) && $_GET['action'] == 'edit' && in_array( $_GET['post'], $this->props->postIds ) ) {
				add_action( 'add_meta_boxes', [ $this, 'initPostMetabox' ], 10 );
			}

			add_action( 'save_post', [ $this, 'updateData' ], 1 );

		}

		if ( $this->props->taxonomies ) {

			$this->initTaxonomyMetabox();

			add_action( 'create_term', [ $this, 'updateData' ], 10 );
			add_action( 'edit_term', [ $this, 'updateData' ], 10 );
		}

	}

	public function initTaxonomyMetabox(): void {

		if ( isset( $_GET['taxonomy'] ) && taxonomy_exists( $_GET['taxonomy'] ) ) {
			$taxonomy = $_GET['taxonomy'];
		} else {
			return;
		}

		if ( is_array( $this->props->taxonomies ) && ! in_array( $taxonomy, $this->props->taxonomies ) ) {
			return;
		}

		$this->isTerm = true;
		add_action( $taxonomy . '_add_form_fields', [ $this, 'metaboxContent' ], 10 );
		add_action( $taxonomy . '_edit_form_fields', [ $this, 'metaboxContent' ], 10 );

	}

	public function initPostMetabox( $post_type ) {

		if ( is_array( $this->props->postTypes ) && ! in_array( $post_type, $this->props->postTypes ) ) {
			return false;
		}

		$this->isPost = true;

		add_meta_box( $this->props->id, $this->props->title, [
			$this,
			'metaboxContent'
		], $post_type, $this->props->context, $this->props->priority );

	}

	public function getNonceInput(): string {
		return '<input type="hidden" name="' . $this->props->id . '_nonce" value="' . wp_create_nonce( __FILE__ ) . '" />';
	}

	public function verifyUpdate( $item_id ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

        if( wp_is_post_revision( $item_id ) ){
            return false;
        }

		if ($this->props->hasNonce && (! isset( $_POST[ $this->props->id . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->props->id . '_nonce' ], __FILE__ )) ) {
			return false;
		}

		if ( isset( $_POST['post_type'] ) && $this->props->postTypes ) {

			if ( is_array( $this->props->postTypes ) && ! in_array( $_POST['post_type'], $this->props->postTypes ) ) {
				return false;
			}

			if ( ! current_user_can( 'edit_post', $item_id ) ) {
				return false;
			}

			$this->isPost = true;

		} elseif ( isset( $_POST['taxonomy'] ) && $this->props->taxonomies ) {

			if ( is_array( $this->props->taxonomies ) && ! in_array( $_POST['taxonomy'], $this->props->taxonomies ) ) {
				return false;
			}
			if ( ! current_user_can( 'manage_categories' ) ) {
				return false;
			}

			$this->isTerm = true;

		}

		return true;
	}

	public function getFieldsContent( $fields ): string {
		$content = '';
		foreach ( $fields as $field ) {
			if ( is_array( $field ) ) {
				$childHtml = '';
				foreach ( $field as $k => $f ) {
					if ( ! $k ) {
						continue;
					}
					$childHtml .= $f->get_input();
				}
				$content .= $field[0]->get_html( $childHtml );
			} else {
				$content .= $field->get_html();
			}
		}

		return $content;
	}

	public function getTaxonomyTable( $fields ): string {

		if ( empty( $_GET['tag_ID'] ) ) {
			$content = '<h3>' . $this->props->title . '</h3>';
			$content .= $this->getFieldsContent( $fields );
		} else {
			$content = '<table class="form-table" role="presentation">';

			$content .= '<tr>';
			$content .= '<th>' . $this->props->title . '</th>';
			$content .= '<td>';

			$content .= $this->getFieldsContent( $fields );

			$content .= '</td>';
			$content .= '<tr/>';

			$content .= '</table>';
		}

		return $content;

	}

	public function termWrapper( $content ): string {
		return '<tr class="form-field"><td colspan="2">' . $content . '</td></tr>';
	}

	public function metaboxContent( mixed $item ): void {

		$content = $this->getContent( $item );

		$content .= $this->getNonceInput();

		echo $content;

	}

	public function updateData( int $itemId ): void {
		if ( ! $this->verifyUpdate( $itemId ) ) {
			return;
		}

		$this->update( $itemId );
	}

}
