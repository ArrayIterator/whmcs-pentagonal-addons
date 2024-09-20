<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries\Generator\Menu;

use Psr\Http\Message\UriInterface;
use function is_array;
use function is_callable;
use function is_int;
use function is_string;

class Menu extends AbstractMenu
{
    /**
     * Create Menu from Array
     *
     * @param Menus $menus
     * @param array $definitions
     * @param $menuId
     * @return void
     */
    public static function createFromArray(Menus $menus, $menuId, array $definitions) : Menu
    {
        $id = $menuId;
        $link = $definitions['link']??null;
        $link = !is_string($link) && ($link instanceof UriInterface) ? null : $link;
        $attributes = $definitions['attributes']??[];
        $attributes = !is_array($attributes) ? [] : $attributes;
        $priority = $definitions['priority']??10;
        $priority = !is_int($priority) ? 10 : $priority;
        $linkText = $definitions['linkText']??($definitions['text']??null);
        $linkText = !is_string($linkText) ? null : $linkText;
        $callback = $definitions['callablePermission']??(
            $definitions['callback']??null
        );
        $callback = !is_callable($callback) ? null : $callback;
        $menu = new static(
            $menus,
            $id,
            $attributes,
            $priority,
            $link,
            $linkText,
            $callback
        );
        $submenus = $definitions['subMenus']??(
            $definitions['submenus']??(
                $definitions['sub_menus']??(
                    $definitions['sub_menu']??[]
                )
            )
        );
        if (is_array($submenus)) {
            foreach ($submenus as $subMenuId => $subMenu) {
                if (!is_string($subMenuId) || !is_array($subMenu)) {
                    continue;
                }
                $sub = static::createFromArray(
                    $menus,
                    $subMenuId,
                    $subMenu
                );
                $menu->addSubMenu($sub);
            }
        }
        return $menu;
    }
}
