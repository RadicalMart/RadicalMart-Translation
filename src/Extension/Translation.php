<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_system_radicalmart
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMart\Translation\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\RadicalMart\Administrator\Helper\LanguagesHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PluginsHelper;
use Joomla\Component\RadicalMart\Administrator\View\FormView;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Path;

class Translation extends CMSPlugin implements SubscriberInterface
{
	use MVCFactoryAwareTrait;
	use DatabaseAwareTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartPrepareForm'     => 'onRadicalMartPrepareForm',
			'onRadicalMartPrepareViewTabs' => 'onRadicalMartPrepareViewTabs',
		];
	}

	/**
	 * Method to load translate forms to RadicalMart forms.
	 *
	 * @param   Form   $form  The form object to be modified.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareForm(Form $form, mixed $data): void
	{
		try
		{
			$formName = $form->getName();
			if ($formName === 'com_radicalmart.category')
			{
				$this->loadTranslateForm($form, 'com_radicalmart.category', $data);
			}
		}
		catch (\Throwable $e)
		{
			throw new \RuntimeException('Translation Forms: ' . $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Method to load translate form.
	 *
	 * @param   Form    $form  The source form object to be modified.
	 * @param   string  $name  The translate form name.
	 * @param   mixed   $data  The associated data for the form.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function loadTranslateForm(Form $form, string $name, mixed $data = []): void
	{
		$file     = $name . '.xml';
		$filename = Path::clean(JPATH_PLUGINS . '/radicalmart/translation/forms/' . $file);
		if (!is_file($filename))
		{
			throw new \Exception('Form File `' . $file . '` not found.');
		}

		/** @var Form $translateForm */
		$translateForm = Factory::getContainer()->get(FormFactoryInterface::class)
			->createForm($name . '.translation', ['control' => null, 'load_data' => false]);
		$translateForm->loadFile($filename);

		/**
		 * Trigger `onRadicalMartPrepareTranslateForm` event.
		 *
		 * @param   Form    $translateForm  The translate form object to be modified.
		 * @param   Form    $form           The source form object.
		 * @param   string  $name           The translate form name.
		 * @param   mixed   $data           The associated data for the form.
		 *
		 * @return  void
		 */
		PluginsHelper::triggerPlugins(['radicalmart', 'system'], 'onRadicalMartPrepareTranslateForm',
			[$translateForm, $form, $name, $data]);

		$translateForm = $translateForm->getXml()->asXML();

		$languages   = LanguageHelper::getContentLanguages();
		$default     = LanguagesHelper::getDefaultTag('site');
		$empty_image = HTMLHelper::image('empty/empty', '', relative: true);
		foreach ($languages as $language)
		{
			$image = HTMLHelper::image('mod_languages/' . $language->image . '.gif', '', relative: true);

			$title        = htmlspecialchars($language->title);
			$title_image  = $title;
			$image_render = '';
			if ($image !== $empty_image)
			{
				$image_render = htmlspecialchars($image);
				$title_image  = htmlspecialchars($image . ' ' . $language->title);
			}

			$display_class = ($language->lang_code === $default) ? 'd-none hide' : '';

			$xml = str_replace('{language_code}', $language->lang_code, $translateForm);
			$xml = str_replace('{language_title}', $title, $xml);
			$xml = str_replace('{language_title_image}', $title_image, $xml);
			$xml = str_replace('{language_image_render}', $image_render, $xml);
			$xml = str_replace('{language_display_class}', $display_class, $xml);

			$form->load($xml);
		}
	}

	/**
	 * Trigger `onRadicalMartPrepareViewTabs` event.
	 *
	 * @param   array     $tabs  Modified view tabs.
	 * @param   FormView  $view  Current form view class object.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareViewTabs(array &$tabs, FormView $view): void
	{
		$context = $view->getContext();
		$form    = $view->getForm();
		if (!$form)
		{
			return;
		}

		if ($context === 'com_radicalmart.category')
		{
			$this->addTranslationTab($tabs, $form);
		}
	}

	/**
	 * Method to add translation tab to form.
	 *
	 * @param   array  $tabs  Current tabs array.
	 * @param   Form   $form  Current form object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function addTranslationTab(array &$tabs, Form $form): void
	{
		$fieldsets = [];
		foreach ($form->getFieldsets() as $fieldset)
		{
			if (!str_starts_with($fieldset->name, 'translation_'))
			{
				continue;
			}
			$fieldsets[] = $fieldset->name;
		}

		if (count($fieldsets) === 0)
		{
			return;
		}

		$tabs['translation'] = [
			'title'      => 'PLG_RADICALMART_TRANSLATION_TAB',
			'fieldsets'  => $fieldsets,
			'full_width' => true,
			'ordering'   => 103,
		];
	}
}