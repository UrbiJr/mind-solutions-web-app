<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Service\Utils;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * See https://symfony.com/doc/current/templating/twig_extension.html.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Julien ITARD <julienitard@gmail.com>
 */
final class AppExtension extends AbstractExtension
{
    /**
     * @var string[]
     */
    private readonly array $localeCodes;

    /**
     * @var list<array{code: string, name: string}>|null
     */
    private ?array $locales = null;

    // The $locales argument is injected thanks to the service container.
    // See https://symfony.com/doc/current/service_container.html#binding-arguments-by-name-or-type
    public function __construct(
        string $locales,
        private readonly MemcachedAdapter $cache,
        private readonly Utils $utils
    ) {
        $localeCodes = explode('|', $locales);
        sort($localeCodes);
        $this->localeCodes = $localeCodes;
    }

    public function getFunctions(): array
    {
        parent::getFunctions();
        return [
            new TwigFunction('locales', $this->getLocales(...)),
            new TwigFunction('viagogoUser', [$this, 'getViagogoUser']),
            new TwigFunction('currencyStringToSymbol', [$this->utils, 'currencyStringToSymbol']),
            new TwigFunction('currencySymbolToString', [$this->utils, 'currencySymbolToString']),
            new TwigFunction('formatAmountArrayAsSymbol', [$this->utils, 'formatAmountArrayAsSymbol']),
            new TwigFunction('formatAmountAndCurrencyAsSymbol', [$this->utils, 'formatAmountAndCurrencyAsSymbol']),
        ];
    }

    public function getFilters()
    {
        parent::getFilters();
        return [
            new TwigFilter('sanitizeTicketGenreAsset', [$this, 'sanitizeTicketGenreAsset']),
        ];
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.).
     *
     * @return array<int, array<string, string>>
     */
    public function getLocales(): array
    {
        if (null !== $this->locales) {
            return $this->locales;
        }

        $this->locales = [];
        foreach ($this->localeCodes as $localeCode) {
            $this->locales[] = ['code' => $localeCode, 'name' => Locales::getName($localeCode, $localeCode)];
        }

        return $this->locales;
    }

    public function getViagogoUser($userId)
    {
        $cacheItem = $this->cache->getItem("viagogoUser_" . $userId);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        } else {
            return null;
        }
    }

    public function sanitizeTicketGenreAsset($ticketGenre)
    {
        switch ($ticketGenre) {
            case "Concert Tickets":
                return "Concert_Tickets";

            case "Sports Tickets":
                return "Sports_Tickets";

            case "Theater Tickets":
                return "Theater_Tickets";

            default:
                return "Concert_Tickets";
        }
    }
}
