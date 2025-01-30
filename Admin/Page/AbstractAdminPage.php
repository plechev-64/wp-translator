<?php

namespace Src\Module\Translator\Admin\Page;

abstract class AbstractAdminPage
{

	private PropsAdminPage $props;

	abstract protected function getProps(): PropsAdminPage;

	abstract protected function update(): void;

    public function init(): void
    {

	    $this->props = $this->getProps();

	    add_action('admin_menu', function(){
		    $this->menuInit();
	    }, $this->props->priority);

	    add_action('admin_init', function(){
			$this->updateData();
	    }, $this->props->priority);
    }

	private function menuInit(): void {

		$menuLabel = $this->props->title;
		if($this->props->counter) {
			$cnt = 0;
			if(is_callable($this->props->counter)){
				$counterCallable = $this->props->counter;
				$cnt = $counterCallable();
			}else if(is_numeric($this->props->counter)){
				$cnt = $this->props->counter;
			}
			if($cnt) {
				$menuLabel .= " <span class='update-plugins count-1'><span class='update-count'>$cnt </span></span>";
			}
		}

		if ($this->props->parent) {
			add_submenu_page($this->props->parent, $this->props->title, $menuLabel, $this->props->right, $this->props->id, function(){
				$this->pageContent();
			});
		} else {
			add_menu_page($this->props->title, $menuLabel, $this->props->right, $this->props->id, function(){
				$this->pageContent();
			});
		}
	}

    protected function pageContent(): void
    {

        $content = '<div class="wrap pkr-page">';
        $content .= '<h1>' . $this->props->title . '</h1>';

        $content .= '<div class="postbox" style="padding: 20px;margin:20px 0;">';
        $content .= $this->getContent();
        $content .= '</div>';

        $content .= '</div>';
        echo $content;
    }

    protected function getContent(): string
    {
        return $this->getFormContent();
    }

	protected function getFormContent(): string
    {
        $content = '<form method="post" enctype="multipart/form-data">';
        $content .= $this->getForm();
        $content .= $this->getNonceField();
        $content .= '<div class="submit-wrap">' . get_submit_button() . '</div>';
        $content .= '</form>';
        return $content;
    }

	protected function getForm(): string
    {
		return '';
    }

	protected function getNonceField(): string {
        return '<input type="hidden" name="' . $this->props->id . '_nonce" value="' . wp_create_nonce(__FILE__) . '" />';
    }

	protected function updateData(): void
    {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST[ $this->props->id . '_nonce' ]) || !wp_verify_nonce($_POST[ $this->props->id . '_nonce' ], __FILE__)) {
            return;
        }

        if (!current_user_can($this->props->right)) {
            return;
        }

        $this->update();

    }

}
