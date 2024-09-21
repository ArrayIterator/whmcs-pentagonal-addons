<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries\Generator\Menu;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use function array_key_exists;
use function call_user_func;
use function is_callable;
use function is_object;
use function is_string;
use function method_exists;
use function uasort;

abstract class AbstractMenu
{
    /**
     * @var Menus $menus Menus Instance
     */
    protected Menus $menus;

    /**
     * @var int $menuIncrement Incremental Menu
     */
    private static int $menuIncrement = 0;

    /**
     * @var string $id Menu ID
     */
    protected string $id = '';

    /**
     * @var int $priority Menu Priority
     */
    protected int $priority = 10;

    /**
     * @var string|mixed|null $link Menu Link
     */
    protected $link = null;

    /**
     * @var string|null $linkText Menu Link Text
     */
    protected ?string $linkText = null;

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * @var array<string, AbstractMenu>
     */
    private array $subMenus = [];

    /**
     * @var ?callable $callablePermission Callable Permission
     */
    protected $callablePermission;

    /**
     * @param Menus $menus
     * @param string $id
     * @param array $attributes
     * @param int $priority
     * @param $link
     * @param string|null $linkText
     * @param callable|null $callablePermission
     */
    public function __construct(
        Menus $menus,
        string $id,
        array $attributes = [],
        int $priority = 10,
        $link = null,
        ?string $linkText = null,
        ?callable $callablePermission = null
    ) {
        $this->menus = $menus;
        $this->id = $id;
        $this->priority = $priority;
        $this->attributes = $attributes;
        $this->setLink($link)
            ->setAttributes($attributes)
            ->setLinkText($linkText);
        $this->callablePermission = $callablePermission;
    }

    /**
     * @return Menus Menus Instance
     */
    public function getMenus(): Menus
    {
        return $this->menus;
    }

    /**
     * Call Permission
     *
     * @param ServerRequestInterface|null $request
     * @return bool
     */
    public function permitted(
        ?ServerRequestInterface $request = null
    ) : bool {
        $res = is_callable($this->callablePermission)
            ? call_user_func($this->callablePermission, $request, $this)
            : true;
        return $res === true;
    }

    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param UriInterface|string|\Stringable $link
     * @return $this
     */
    public function setLink($link) : AbstractMenu
    {
        if (is_string($link)
            || $link instanceof UriInterface
            || is_object($link) && method_exists($link, '__toString')
        ) {
            $this->link = $link;
        }
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

    public function setAttributes(array $attributes): self
    {
        $this->attributes = [];
        foreach ($attributes as $key => $item) {
            if (is_string($key)) {
                $this->setAttribute($key, $item);
            }
        }
        return $this;
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

    /**
     * Check if has menu
     *
     * @param string $menuId
     * @return bool
     */
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
