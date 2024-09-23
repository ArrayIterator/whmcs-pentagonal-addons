<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries\Generator\Menu;

use ArrayIterator;
use IteratorAggregate;
use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\HtmlAttributes;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Stringable;
use Traversable;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_contains;
use function trim;
use function uasort;

class Menus implements Stringable, IteratorAggregate
{
    /**
     * @var array<string, AbstractMenu> $menus Menu lists
     */
    private array $menus = [];

    /**
     * @var int $menusIncrement Increment
     */
    private static int $menusIncrement = 0;

    /**
     * @var EventManager $eventManager the Event Manager
     */
    protected EventManager $eventManager;

    /**
     * @var Core $core
     */
    protected Core $core;

    /**
     * Menus constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
        $this->setEventManager($core->getEventManager());
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * Get Event Manager
     *
     * @return EventManager
     */
    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    /**
     * Set Event Manager
     *
     * @param EventManager $eventManager
     * @return void
     */
    public function setEventManager(EventManager $eventManager): void
    {
        $this->eventManager = $eventManager;
    }

    public function getMenus(): array
    {
        uasort(
            $this->menus,
            function (AbstractMenu $a, AbstractMenu $b) {
                $a = $a->getPriority();
                $b = $b->getPriority();
                return $a === $b ? 0 : ($a < $b ? -1 : 1);
            }
        );

        return $this->menus;
    }

    /**
     * Add Menu
     *
     * @param AbstractMenu $menu
     * @return bool
     */
    public function addMenu(AbstractMenu $menu) : bool
    {
        $currentMenu = $this->menus[$menu->getId()]??null;
        if ($currentMenu) {
            return false;
        }
        $this->menus[$menu->getId()] = $menu;
        return true;
    }

    /**
     * Replace Menu
     *
     * @param AbstractMenu $menu
     * @return AbstractMenu|null
     */
    public function replaceMenu(AbstractMenu $menu) : ?AbstractMenu
    {
        $previousMenu = $this->menus[$menu->getId()]??null;
        $this->menus[$menu->getId()] = $menu;
        return $previousMenu;
    }

    /**
     * Check Menu Exist
     *
     * @param string $menuId
     * @return bool
     */
    public function hasMenu(string $menuId) : bool
    {
        return isset($this->menus[$menuId]);
    }

    /**
     * Remove Menu
     *
     * @param string $menuId
     * @return AbstractMenu|null
     */
    public function removeMenu(string $menuId): ?AbstractMenu
    {
        $menu = null;
        if (isset($this->menus[$menuId])) {
            $menu = $this->menus[$menuId];
            unset($this->menus[$menuId]);
        }
        return $menu;
    }

    /**
     * Create Link Tag
     *
     * @param AbstractMenu $menu
     * @return string
     */
    public function createLinkTag(AbstractMenu $menu) : string
    {
        $uri = $menu->getLink();
        $linkText = $menu->getLinkText();
        if ($uri instanceof UriInterface) {
            $uri = (string) $uri;
        }
        if ($linkText === null && $uri === null) {
            return '';
        }
        $originalAttribute = [
            'class' => [
                'menu-link'
            ],
        ];
        $manager = $this->getEventManager();
        $attributes = $manager->apply(
            'menusLinkAttributes',
            $originalAttribute,
            $menu,
            $this
        );
        $attributes = is_array($attributes)
            ? $attributes
            : $originalAttribute;
        $linkText = $linkText
        && str_contains($linkText, '<') // contain tags
            ? DataNormalizer::forceBalanceTags($linkText)
            : ($linkText??'');
        return sprintf(
            '<%1$s href="%2$s" %3$s>%4$s</%1$s>',
            'a',
            $uri,
            HtmlAttributes::buildAttributes($attributes),
            $linkText
        );
    }

    /**
     * Create Menu Attribute Id
     *
     * @param AbstractMenu $menu
     * @param int $depth
     * @return string
     */
    private function createMenuAttributeId(AbstractMenu $menu, int $depth = 0): string
    {
        $menuId = 'menu-';
        if (self::$menusIncrement > 1) {
            $menuId .= sprintf('inc-%d-', self::$menusIncrement -1);
        }
        $menuId .= DataNormalizer::normalizeHtmlClass($menu->getId());
        if ($depth > 0) {
            $menuId .= '-depth-' . $depth;
        }

        return $menuId;
    }

    /**
     * Append Attribute List Request
     *
     * @param array $attributes
     * @param ServerRequestInterface|null $request
     * @param AbstractMenu $menu
     * @param $hasCurrent
     * @return array
     */
    protected function appendAttributeListRequest(
        array $attributes,
        ?ServerRequestInterface $request,
        AbstractMenu $menu,
        &$hasCurrent = null
    ) : array {
        unset($attributes['data-current']);
        $link = $menu->getLink();
        $attributes['class'] = DataNormalizer::splitStringToArray($attributes['class']??[])??[];
        $attributes['class'][] = 'menu-list';
        if (!$request || $link === null) {
            return $attributes;
        }
        if (is_string($link)) {
            $link = $this->getCore()->getHttpFactory()->getUriFactory()->createUri($link);
            if ($link->getHost() === '') {
                $link = $link->withHost($request->getUri()->getHost());
            }
        }
        $uri = $request->getUri();
        if ($link->getHost() === $uri->getHost()
            && trim($uri->getPath(), '/') === trim($link->getPath(), '/')
        ) {
            $attributes['class'][] = 'menu-list-current';
            $attributes['data-current'] = true;
            $hasCurrent = true;
        }

        return $attributes;
    }

    /**
     * Render Menu
     *
     * @param ServerRequestInterface|null $request
     * @param AbstractMenu $menu
     * @param string $tag
     * @param int $maxDepth
     * @param int $depth
     * @param $hasCurrent
     * @return string
     */
    private function renderMenu(
        ?ServerRequestInterface $request,
        AbstractMenu $menu,
        string $tag,
        int $maxDepth,
        int $depth = 0,
        &$hasCurrent = null
    ) : string {
        if (!$menu->permitted($request)
            || $depth < 0
            || $maxDepth < $depth
        ) {
            return '';
        }
        $subListTag = $tag === 'div' ? 'div' : 'li';

        $html = '';
        $countMenu = 0;
        foreach ($menu->getSubMenus() as $subMenu) {
            if ($subMenu->permitted($request) === false) {
                continue;
            }
            $countMenu++;
            $originalAttribute = [
                    'id' => $this->createMenuAttributeId($subMenu, $depth + 1),
                ] + $menu->getAttributes();
            $em = $this->getEventManager();
            $attributes = $em->apply(
                'menusSubmenuAttributes',
                $originalAttribute,
                $subMenu,
                $menu,
                $depth,
                $this
            );
            $attributes = is_array($attributes)
                ? $attributes
                : $originalAttribute;
            $containCurrent = false;
            $subMenuHtml = $this->renderMenu(
                $request,
                $menu,
                $tag,
                $maxDepth,
                $depth+1,
                $containCurrent
            );

            $attributes = $this->appendAttributeListRequest($attributes, $request, $subMenu, $hasCurrent);
            if ($containCurrent) {
                $hasCurrent = true;
                $attributes['data-has-current-submenu'] = true;
                $attributes['class'][] = 'has-current-submenu';
            }
            if ($containCurrent || $subMenuHtml) {
                $attributes['class'][] = 'has-submenu';
                $attributes['data-has-submenu'] = true;
            }

            $html .= sprintf(
                '<%1$s %2$s>%3$s%4$s</%1$s>',
                $subListTag,
                HtmlAttributes::buildAttributes($attributes),
                $this->createLinkTag($subMenu),
                $subMenuHtml
            );
        }

        $parentAttributes = [
            'class' => ['submenu'],
            'data-depth' => $depth+1
        ];
        return $this->createMenuStructure($tag, $html, $countMenu, $parentAttributes);
    }

    /**
     * Display Menu
     *
     * @param ServerRequestInterface|null $request
     * @param string $listTag
     * @param int $maxDepth
     * @param array $attributes
     * @return string
     */
    public function display(
        ?ServerRequestInterface $request = null,
        string $listTag = 'ul',
        int $maxDepth = 0,
        array $attributes = []
    ) : string {
        self::$menusIncrement++;
        if ($maxDepth < 0) {
            return '';
        }
        $request = $request??$this->getCore()->getRequest();
        $tag = !in_array($listTag, ['ul', 'ol']) ? 'div' : $listTag;
        $subListTag = $tag === 'div' ? 'div' : 'li';

        $em = $this->getEventManager();
        // filter classes
        $attributes['class'] = DataNormalizer::splitStringToArray($attributes['class']??[])??[];
        $attributes['data-depth'] = 0;
        $attributes['class'][] = 'parent-menu';
        $parentAttributes = $attributes;
        $html = '';
        $countMenu = 0;
        foreach ($this->getMenus() as $menu) {
            $countMenu++;
            $originalAttribute = [
                    'id' => $this->createMenuAttributeId($menu)
                ] + $menu->getAttributes();
            $attributes = $em->apply(
                'menusMenuAttributes',
                $originalAttribute,
                $menu,
                $this
            );
            $attributes = is_array($attributes)
                ? $attributes
                : $originalAttribute;
            $hasCurrent = false;
            $subMenu = $this->renderMenu(
                $request,
                $menu,
                $tag,
                $maxDepth,
                0,
                $hasCurrent
            );
            $attributes = $this->appendAttributeListRequest($attributes, $request, $menu, $hasCurrent);
            if ($hasCurrent || $subMenu) {
                $attributes['class'][] = 'has-submenu';
                $attributes['data-has-submenu'] = true;
            }
            if ($hasCurrent) {
                $attributes['class'][] = 'has-current-submenu';
                $attributes['data-current-submenu'] = true;
            }

            $html .= sprintf(
                '<%1$s %2$s>%3$s%4$s</%1$s>',
                $subListTag,
                HtmlAttributes::buildAttributes($attributes),
                $this->createLinkTag($menu),
                $subMenu
            );
        }
        return $this->createMenuStructure($tag, $html, $countMenu, $parentAttributes);
    }

    /**
     * Create menu structure
     *
     * @param string $tag
     * @param string $html
     * @param int $countMenu
     * @param array $parentAttributes
     * @return string
     */
    private function createMenuStructure(string $tag, string $html, int $countMenu, array $parentAttributes) : string
    {
        $html = trim($html);
        if ($html !== '') {
            $parentAttributes['class'][] = 'contain-menu';
            $parentAttributes['data-has-submenu'] = true;
            $parentAttributes['data-submenu-count'] = $countMenu;
        } else {
            $parentAttributes['class'][] = 'empty-menu';
        }
        return HtmlAttributes::buildTag($tag, $parentAttributes, $html);
    }

    /**
     * Create Menus from Array
     *
     * @param array $collections
     * @param Core $core
     * @return Menus
     */
    public static function createFromArray(array $collections, Core $core) : Menus
    {
        $menus = new static($core);
        foreach ($collections as $id => $definitions) {
            if ($definitions instanceof Menu) {
                $menus->addMenu($definitions);
                continue;
            }
            if (!is_string($id) || !is_array($definitions)) {
                continue;
            }
            $definitions = Menu::createFromArray($menus, $id, $definitions);
            $menus->addMenu($definitions);
        }
        return $menus;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->display();
    }

    /**
     * @return Traversable<AbstractMenu>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->getMenus());
    }
}
