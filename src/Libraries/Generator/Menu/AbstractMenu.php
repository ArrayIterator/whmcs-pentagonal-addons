<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries\Generator\Menu;

use Psr\Http\Message\ServerRequestInterface;
use function array_key_exists;
use function is_string;
use function uasort;

abstract class AbstractMenu
{
    protected $menus;

    private static $menuIncrement = 0;

    protected $id = '';

    protected $priority = 10;

    protected $link = null;

    protected $linkText = null;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array<string, AbstractMenu>
     */
    private $subMenus = [];

    public function __construct(Menus $menus)
    {
        $this->menus = $menus;
        // reset
        $this->setAttributes($this->attributes);
    }

    public function getMenus(): Menus
    {
        return $this->menus;
    }

    public function permitted(
        ?ServerRequestInterface $request = null
    ) : bool {
        return true;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link) : AbstractMenu
    {
        $this->link = $link;
        return $this;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText;
    }

    /**
     * @param string|null $linkText
     * @return $this
     */
    public function setLinkText(?string $linkText): AbstractMenu
    {
        $this->linkText = $linkText;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = [];
        foreach ($attributes as $key => $item) {
            if (is_string($key)) {
                $this->setAttribute($key, $item);
            }
        }
    }

    public function setAttribute(string $attributeName, $attributeValue): AbstractMenu
    {
        $this->attributes[$attributeName] = $attributeValue;
        return $this;
    }

    public function getAttribute(string $attributeName)
    {
        return $this->attributes[$attributeName]??null;
    }

    public function hasAttribute(string $attributeName) : bool
    {
        return array_key_exists($attributeName, $this->getAttributes());
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        if ($this->id === '') {
            $this->id = 'menu-' . ++self::$menuIncrement;
        }
        return $this->id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function addSubmenu(AbstractMenu $menu) : AbstractMenu
    {
        $this->subMenus[$menu->getId()] = $menu;
        return $this;
    }

    public function getSubMenus(): array
    {
        uasort(
            $this->subMenus,
            function (AbstractMenu $a, AbstractMenu $b) {
                $a = $a->getPriority();
                $b = $b->getPriority();
                return $a === $b ? 0 : ($a < $b ? -1 : 1);
            }
        );

        return $this->subMenus;
    }

    public function hasSubmenu(string $menuId) : bool
    {
        return isset($this->subMenus[$menuId]);
    }

    public function removeSubMenu(string $menuId): ?AbstractMenu
    {
        $menu = null;
        if (isset($this->subMenus[$menuId])) {
            $menu = $this->subMenus[$menuId];
            unset($this->subMenus[$menuId]);
        }

        return $menu;
    }

    /**
     * @param Menus $menus
     * @param string $id
     * @param array $attributes
     * @param int $priority
     * @param string|null $link
     * @param string|null $linkText
     * @param callable|null $callablePermission
     * @return AbstractMenu
     */
    public static function create(
        Menus $menus,
        string $id,
        array $attributes = [],
        int $priority = 10,
        ?string $link = null,
        ?string $linkText = null,
        ?callable $callablePermission = null
    ): AbstractMenu {
        return new Menu(
            $menus,
            $id,
            $attributes,
            $priority,
            $link,
            $linkText,
            $callablePermission
        );
    }
}
