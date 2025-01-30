<?php

namespace Src\Module\Translator;

use Doctrine\ORM\EntityManagerInterface;
use Src\Core\DTO\Model\TermDTO;
use Src\Core\Entity\Term;
use Src\Core\Rest\Abstract\AbstractController;
use Src\Core\Rest\Attributes\Route;
use Src\Module\Translator\Admin\Metabox\MenuLanguageMetabox;
use Src\Module\Translator\Command\FillDefaultTranslateCommand;
use Src\Module\Translator\Service\TranslatorStateService;
use Src\Core\Transformer\TransformerManager;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/translator')]
class TranslatorController extends AbstractController
{

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/fill-default', method: 'POST', permission: 'manage_options')]
    public function fillDefaultLanguage(
        FillDefaultTranslateCommand $command
    ): Response
    {
        $command->run(new ArgvInput(), new ConsoleOutput());
        return $this->success([]);
    }

    #[Route(path: '/term/metabox/get', permission: 'manage_categories')]
    public function getTermMetabox(
        int                    $termId,
        string                 $code,
        MenuLanguageMetabox    $menuLanguageMetabox,
        TranslatorStateService $translatorService,
        TransformerManager     $transformerManager,
        EntityManagerInterface $entityManager
    ): Response
    {

        $sourceId = !empty($_GET['trl_source']) ? (int)$_GET['trl_source'] : 0;

        $translatorService->setCurrentLanguageByCode($code, true);

        ob_start();
        $menuLanguageMetabox->init();
        if ($termId) {
            $termId = $menuLanguageMetabox->getCorrectMenuIdByLangCodeAndMenuId($code, $termId);
        }
        $menuLanguageMetabox->metaboxContent(get_term($termId));
        $content = ob_get_clean();

        $source = null;
        if ($sourceId) {
            /** @var Term $term */
            $term = $entityManager->getRepository(Term::class)->find($sourceId);
            $source = $transformerManager->transform($term, TermDTO::class);
        }

        return $this->success(
            [
                'metabox' => $content,
                'menu_id' => $termId,
                'source' => $source
            ]
        );
    }
}