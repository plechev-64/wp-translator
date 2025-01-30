<?php

namespace Src\Module\Translator;

use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Service\TranslatorEntityService;

class TranslatorFilterSql {

	public function __construct(
		private readonly TranslatorState $state,
		private readonly TranslatorEntityService $translatorEntityService
	) {
	}

	public function init(): void {

		add_filter( 'posts_join', function ( string $join, $query ) {
			return $this->filterPostRequest( $join, $query, function ( $join, $postType ) {
				return $this->postsJoinFilter( $join, $postType );
			} );
		}, 10, 2 );

		add_filter( 'posts_where', function ( string $where, $query ) {
			return $this->postsWhereFilter( $where, $query);
		}, 10, 2 );

		add_filter( 'terms_clauses', [$this, 'editTermsClauses'], 10, 2 );
	}

    public function editTermsClauses( $pieces, $taxonomies ) {

        if($this->state->getCurrentLanguage()->code === Config::ALL_LANGUAGES_CODE){
            return $pieces;
        }

        foreach ( $taxonomies as $taxonomy ) {
            if ( $this->translatorEntityService->isSupportEntityType( EntityType::Tax, $taxonomy ) ) {
                $pieces['join']  = $this->termsJoinFilter( $pieces['join'] ?? '', $taxonomy );
                $pieces['where'] = $this->termsWhereFilter( $pieces['where'] ?? '', $taxonomy );
            }
        }

        return $pieces;
    }

    private function postsWhereFilter( string $stringRequest, \WP_Query $query ): string {

        if($this->state->getCurrentLanguage()->code === Config::ALL_LANGUAGES_CODE){
            return $stringRequest;
        }

        if ( $query->is_page ) {
            $postTypes = [ 'page' ];
        } else {
            if ( empty( $query->query['post_type'] ) ) {
                return $stringRequest;
            }

            $postTypes = is_array( $query->query['post_type'] ) ? $query->query['post_type'] : [ $query->query['post_type'] ];
        }

        $whereSupport = [];
        $Unsupported = [];
        foreach ( $postTypes as $postType ) {
            if ( $this->translatorEntityService->isSupportEntityType( EntityType::Post, $postType ) ) {
                $postType = str_replace('-', '', $postType);
                $whereSupport[] = "{$postType}_te.entity_type='" . EntityType::Post->value . "' AND {$postType}_te.code_lang='{$this->state->getCurrentLanguage()->code}'";
            }else{
                $Unsupported[] = $postType;
            }
        }

        if(!$whereSupport){
            return $stringRequest;
        }

        $whereString = implode(" AND ", $whereSupport);

        if($Unsupported){
            $whereString = "(($whereString) OR wp_posts.post_type IN ('".implode("','", $Unsupported)."')) ";
        }

        $stringRequest .= " AND " . $whereString;

        return $stringRequest;

    }

	private function filterPostRequest( string $stringRequest, \WP_Query $query, callable $callable ): string {

		if($this->state->getCurrentLanguage()->code === Config::ALL_LANGUAGES_CODE){
			return $stringRequest;
		}

		if ( $query->is_page ) {
			$postTypes = [ 'page' ];
		} else {
			if ( empty( $query->query['post_type'] ) ) {
				return $stringRequest;
			}

			$postTypes = is_array( $query->query['post_type'] ) ? $query->query['post_type'] : [ $query->query['post_type'] ];
		}

		foreach ( $postTypes as $postType ) {
			if ( $this->translatorEntityService->isSupportEntityType( EntityType::Post, $postType ) ) {
				$stringRequest = $callable( $stringRequest, $postType );
			}
		}

		return $stringRequest;

	}

	private function postsJoinFilter( string $join, string $postType ): string {
		global $wpdb;
        $postType = str_replace('-', '', $postType);
		$join .= " LEFT JOIN {$wpdb->prefix}translator_entities AS {$postType}_te ON {$wpdb->posts}.ID={$postType}_te.entity_id ";

		return $join;
	}

	private function termsWhereFilter( string $where, string $taxonomy ): string {
		$where .= " AND {$taxonomy}_te.entity_type='" . EntityType::Tax->value . "' AND {$taxonomy}_te.code_lang='{$this->state->getCurrentLanguage()->code}' ";

		return $where;
	}

	private function termsJoinFilter( string $join, string $taxonomy ): string {
		global $wpdb;
		$join .= " LEFT JOIN {$wpdb->prefix}translator_entities AS {$taxonomy}_te ON t.term_id={$taxonomy}_te.entity_id ";

		return $join;
	}
}