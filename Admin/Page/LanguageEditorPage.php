<?php

namespace Src\Module\Translator\Admin\Page;

use Doctrine\ORM\EntityManagerInterface;
use Src\Core\Service\AttachmentService;
use Src\Core\Service\FileManager\FileManagerInterface;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Config;
use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\TransformerManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LanguageEditorPage extends AbstractAdminPage
{

    public const PAGE_SLUG = 'translator-editor';

    public function __construct(
        private readonly TranslatorLanguageService $translatorLanguageService,
        private readonly TransformerManager $transformerManager,
        private readonly AttachmentService $attachmentService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    protected function getProps(): PropsAdminPage
    {
        $props           = new PropsAdminPage(self::PAGE_SLUG, __('Редактор языков', 'trl'));
        $props->parent   = Settings::PAGE_SLUG;
        $props->priority = 100;

        return $props;
    }

    protected function getForm(): string
    {

        $isNew = ($_GET['action'] ?? null) === 'new';
        if ($isNew) {
            $languages = [
                new TranslatorLanguage()
            ];
        } else {
            $languages = $this->translatorLanguageService->getUsingLanguages();

            if ( ! $languages) {
                return __('Выберите хотя бы один используемый язык в настройках мультиязычности', 'trl');
            }
        }


        ob_start();

        ?>
        <style>
            #language-editor {
            }

            .language {
                display: flex;
            }

            .language__property {
                padding: 10px;
                margin: 10px;
            }

            .language__property-label {
                font-weight: bold;
                margin-bottom: 10px;
            }

            .language__property-label.required:after {
                content: "*";
                color: red;
            }

            .language__property .hidden {
                display: none;
            }

            .language__property-value {
            }

            .language__property-value input[type="text"] {
                width: 100px;
            }
        </style>
        <?php if ($isNew): ?>
        <h4>Добавление нового языка</h4>
        <input type="hidden" name="action" value="new">
    <?php else: ?>
        <input type="hidden" name="action" value="update">
    <?php endif; ?>
        <div id="language-editor">
            <?php
            /** @var TranslatorLanguage $language */
            foreach ($languages as $language) {
                $this->singleLanguageTemplate($language);
            }
            ?>
        </div>
        <?php if ( ! $isNew): ?>
        <a href="<?php echo add_query_arg(['action' => 'new']); ?>">+ Добавить новый язык</a>
    <?php endif; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const imageTypeSwitchers = document.querySelectorAll('.image-type');

                imageTypeSwitchers.forEach((imageTypeSwitcher) => {
                    imageTypeSwitcher.addEventListener('click',
                        (event) => {
                            const target = event.target;
                            let id = target.getAttribute('data-id');
                            let fileInput = document.querySelector('#image-file-' + id);

                            if (parseInt(target.value)) {
                                fileInput.classList.remove('hidden');
                            } else {
                                fileInput.classList.add('hidden');
                            }
                        }
                    );
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    private function singleLanguageTemplate(TranslatorLanguage $language): void
    {

        $id = $language->getId() ?: md5(time());

        ?>
        <div class="language">
            <div class="language__property">
                <div class="language__property-label required">
                    Англ. наименование
                </div>
                <div class="language__property-value">
                    <input type="text" name="trl[language][<?php echo $id; ?>][english-name]"
                           value="<?php echo $language->getEnglishName(); ?>" required>
                </div>
            </div>
            <div class="language__property">
                <div class="language__property-label required">
                    Нативное наименование
                </div>
                <div class="language__property-value">
                    <input type="text" name="trl[language][<?php echo $id; ?>][native-name]"
                           value="<?php echo $language->getNativeName(); ?>" required>
                </div>
            </div>
            <div class="language__property">
                <div class="language__property-label required">
                    Код
                </div>
                <div class="language__property-value">
                    <input type="text" name="trl[language][<?php echo $id; ?>][code]"
                           value="<?php echo $language->getCode(); ?>" required>
                </div>
            </div>
            <div class="language__property">
                <div class="language__property-label required">
                    Локаль
                </div>
                <div class="language__property-value">
                    <input type="text" name="trl[language][<?php echo $id; ?>][locale]"
                           value="<?php echo $language->getLocale(); ?>" required>
                </div>
            </div>
            <div class="language__property">
                <div class="language__property-label">
                    Текст справа налево
                </div>
                <div class="language__property-value">
                    <input type="checkbox" name="trl[language][<?php echo $id; ?>][is-rtl]"
                           value="1" <?php checked($language->isRtl(), true) ?>>
                </div>
            </div>
            <div class="language__property">
                <div class="language__property-label">
                    Изображение
                    <?php
                    if ($language->getCode()) {
                        echo $language->getFlag()->image;
                    }
                    ?>

                </div>
                <div class="language__property-value">
                    <div class="language__property-value">
                        <?php if ( ! $language->isCustomImage()): ?>
                            <label>
                                По-умолчанию
                                <input class="image-type" data-id="<?php echo $id; ?>" type="radio"
                                       name="trl[language][<?php echo $id; ?>][is-custom-image]" <?php checked(empty($language->isCustomImage()),
                                    true) ?>
                                       value="0">
                            </label>
                        <?php endif; ?>
                        <label>
                            Свое изображение
                            <input class="image-type" data-id="<?php echo $id; ?>" type="radio"
                                   name="trl[language][<?php echo $id; ?>][is-custom-image]" <?php checked(empty($language->isCustomImage()),
                                false) ?>
                                   value="1">
                            <?php if ($language->getCustomImageId()): ?>
                                <input type="hidden" name="trl[language][<?php echo $id; ?>][custom-image-id]"
                                       value="<?php echo $language->getCustomImageId(); ?>">
                            <?php endif; ?>
                        </label>
                    </div>
                    <div class="language__property-value">
                        <div id="image-file-<?php echo $id; ?>"
                             class="<?php echo $language->isCustomImage() ? '' : 'hidden' ?>">
                            <input type="file" accept="image/*" name="flag<?php echo $id; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * @throws TransformerException
     */
    protected function update(): void
    {

        $languages = $_POST['trl']['language'];

        if ( ! $languages) {
            return;
        }

        foreach ($languages as $id => $languageArray) {

            $model = new TranslatorLanguageDTO();

            if ($id && is_numeric($id)) {
                $model->id = $id;
            }

            $model->code          = $languageArray['code'];
            $model->englishName   = $languageArray['english-name'];
            $model->nativeName    = $languageArray['native-name'];
            $model->locale        = $languageArray['locale'];
            $model->isCustomImage = $languageArray['is-custom-image'];
            $model->customImageId = $languageArray['custom-image-id'] ?? null;
            $model->isRtl         = $languageArray['is-rtl'] ?? 0;

            if ($model->isCustomImage && $uploadedFile = $this->getUploadedFile('flag' . $id)) {
                $attachment = $this->attachmentService->uploadAttachment($uploadedFile, 'flags');
                $model->customImageId = $attachment?->getId();
            }

            $this->transformerManager->transform($model, TranslatorLanguage::class);

            if ($_POST['action'] === 'new') {
                Config::addUsingLanguage($model->code);
            }

        }

        $this->entityManager->flush();

        flush_rewrite_rules();

        wp_redirect(admin_url('/admin.php?page=' . self::PAGE_SLUG));
        exit;

    }

    private function getUploadedFile(string $inputName): ?UploadedFile
    {
        if ( ! isset($_FILES[$inputName]) || empty($_FILES[$inputName]['name'])) {
            return null;
        }
        $file = $_FILES[$inputName];

        return new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
    }

}