<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Schachbulle\ContaoTopwertungszahlenBundle\ContaoTopwertungszahlenBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Meldet das Bundle beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
	/**
	 * Gibt die Bundle-Konfiguration zurück.
	 *
	 * {@inheritdoc}
	 */
	public function getBundles(ParserInterface $parser)
	{
		return [
			BundleConfig::create(ContaoTopwertungszahlenBundle::class)
				->setLoadAfter([ContaoCoreBundle::class]),
		];
	}

	/**
	 * Lädt die Routen des Bundles.
	 *
	 * Nötig geworden mit Version 1.7.0: Der Ranglistenlauf wird seither über
	 * eine Backend-Route angestoßen statt über den Direktaufruf der Datei
	 * `bundles/contaotopwertungszahlen/Rangliste.php`.
	 *
	 * {@inheritdoc}
	 */
	public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
	{
		$datei = __DIR__.'/../Resources/config/routing.yml';

		$loader = $resolver->resolve($datei);

		if (false === $loader) {
			return null;
		}

		return $loader->load($datei);
	}
}
