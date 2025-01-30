<?php

namespace Src\Module\Translator\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Core\Transformer\Exception\TransformerException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand( name: 'app:translate:install', description: 'Заполнение справочников и первоначальных данных' )]
class TranslatorInstallCommand extends Command {

	public function __construct(
		private readonly EntityManagerInterface $entityManager,
	) {
		parent::__construct();
	}

	/**
	 * @throws Exception
	 * @throws TransformerException
	 */
	#[NoReturn] protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		$io = new SymfonyStyle( $input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output );

        $this->entityManager->getConnection()
            ->prepare("TRUNCATE TABLE wp_translator_entities")
            ->executeQuery();

        $this->entityManager->getConnection()
            ->prepare("TRUNCATE TABLE wp_translator_groups CASCADE")
            ->executeQuery();

        $this->entityManager->getConnection()
            ->prepare("TRUNCATE TABLE wp_translator_languages")
            ->executeQuery();

		$this->install( $output, $io );

		return 0;
	}

	/**
	 * @throws Exception
	 * @throws TransformerException
	 */
	private function install( OutputInterface $output, SymfonyStyle $io ): void {

		/**
		 * @var array $languages
		 */
		require_once dirname( __DIR__, 1 ) . '/Install/languages.php';

		if ( ! $languages ) {
			$io->error( 'Не удалось получить языки' );

			return;
		}

		$io->success( 'Начинаем заполнение справочинка языков' );

		$progressBar = new ProgressBar( $output, count( $languages ) );
		$progressBar->start();

		/** @var array $languageArray */
		foreach ( $languages as $languageArray ) {

			$progressBar->advance();

			/** @var TranslatorLanguage|null $language */
			$language = $this->entityManager->getRepository( TranslatorLanguage::class )->findOneBy( [
				'code' => $languageArray['code']
			] );

			if ( ! $language ) {
				$language = new TranslatorLanguage();
				$language->setCode( $languageArray['code'] );
				$this->entityManager->persist( $language );
			}

			$language->setEnglishName( $languageArray['english_name'] );
			$language->setNativeName( $languageArray['native_name'] ?: null );
			$language->setLocale( $languageArray['locale'] );
			$language->setIsRtl( $languageArray['rtl'] );

		}

		$progressBar->finish();

		$this->entityManager->flush();

		$io->success( 'Справочник языков заполнен' );

	}
}