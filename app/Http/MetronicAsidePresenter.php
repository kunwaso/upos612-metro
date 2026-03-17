<?php

namespace App\Http;

use Nwidart\Menus\MenuItem;
use Nwidart\Menus\Presenters\Presenter;

class MetronicAsidePresenter extends Presenter
{
    /**
     * {@inheritdoc}
     */
    public function getOpenTagWrapper()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCloseTagWrapper()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuWithoutDropdownWrapper($item)
    {
        $linkClasses = 'menu-link' . $this->getActiveState($item);

        return '<div class="menu-item">' .
            '<a class="' . $linkClasses . '" href="' . $item->getUrl() . '"' . $this->formatAttributes($item->getAttributes()) . '>' .
            $this->renderMenuIcon($item->icon) .
            '<span class="menu-title">' . $item->title . '</span>' .
            '</a>' .
            '</div>' . PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    public function getDividerWrapper()
    {
        return '<div class="menu-item"><div class="separator my-2"></div></div>' . PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderWrapper($item)
    {
        return '<div class="menu-item pt-5">' .
            '<div class="menu-content">' .
            '<span class="menu-heading fw-bold text-uppercase fs-7">' . $item->title . '</span>' .
            '</div>' .
            '</div>' . PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuWithDropDownWrapper($item)
    {
        return $this->renderAccordion($item, false);
    }

    /**
     * Get multilevel dropdown wrapper.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    public function getMultiLevelDropdownWrapper($item)
    {
        return $this->renderAccordion($item, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildMenuItems(MenuItem $item)
    {
        $results = '';

        foreach ($item->getChilds() as $child) {
            if ($child->hidden()) {
                continue;
            }

            if ($child->hasSubMenu()) {
                $results .= $this->getMultiLevelDropdownWrapper($child);
            } elseif ($child->isHeader()) {
                $results .= $this->getHeaderWrapper($child);
            } elseif ($child->isDivider()) {
                $results .= $this->getDividerWrapper();
            } else {
                $results .= $this->renderChildLink($child);
            }
        }

        return $results;
    }

    /**
     * Get active state for a link.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @param  string  $state
     * @return string|null
     */
    public function getActiveState($item, $state = ' active')
    {
        return $item->isActive() ? $state : null;
    }

    /**
     * Get active/open state for parent items.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @param  string  $state
     * @return string|null
     */
    public function getActiveStateOnChild($item, $state = ' here show')
    {
        return $this->isExpanded($item) ? $state : null;
    }

    /**
     * Render accordion menu item markup.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @param  bool  $useBulletWhenNoIcon
     * @return string
     */
    protected function renderAccordion(MenuItem $item, $useBulletWhenNoIcon)
    {
        $itemClasses = 'menu-item menu-accordion' . $this->getActiveStateOnChild($item);
        $linkClasses = 'menu-link' . ($this->isExpanded($item) ? ' active' : '');

        return '<div class="' . $itemClasses . '" data-kt-menu-trigger="click">' .
            '<span class="' . $linkClasses . '"' . $this->formatAttributes($item->getAttributes()) . '>' .
            $this->renderAccordionPrefix($item, $useBulletWhenNoIcon) .
            '<span class="menu-title">' . $item->title . '</span>' .
            '<span class="menu-arrow"></span>' .
            '</span>' .
            '<div class="menu-sub menu-sub-accordion">' .
            $this->getChildMenuItems($item) .
            '</div>' .
            '</div>' . PHP_EOL;
    }

    /**
     * Render a child leaf link.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    protected function renderChildLink(MenuItem $item)
    {
        $linkClasses = 'menu-link' . $this->getActiveState($item);

        return '<div class="menu-item">' .
            '<a class="' . $linkClasses . '" href="' . $item->getUrl() . '"' . $this->formatAttributes($item->getAttributes()) . '>' .
            '<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>' .
            '<span class="menu-title">' . $item->title . '</span>' .
            '</a>' .
            '</div>' . PHP_EOL;
    }

    /**
     * Render accordion link prefix.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @param  bool  $useBulletWhenNoIcon
     * @return string
     */
    protected function renderAccordionPrefix(MenuItem $item, $useBulletWhenNoIcon)
    {
        if (!empty(trim((string) $item->icon))) {
            return $this->renderMenuIcon($item->icon);
        }

        if ($useBulletWhenNoIcon) {
            return '<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>';
        }

        return '<span class="menu-icon"></span>';
    }

    /**
     * Render menu icon wrapper.
     *
     * @param  string|null  $icon
     * @return string
     */
    protected function renderMenuIcon($icon)
    {
        return '<span class="menu-icon">' . $this->formatIcon($icon) . '</span>';
    }

    /**
     * Format icon HTML.
     *
     * @param  string|null  $icon
     * @return string
     */
    protected function formatIcon($icon)
    {
        $icon = trim((string) $icon);

        if ($icon === '') {
            return '';
        }

        if (stripos($icon, '<svg') !== false || stripos($icon, '<i') !== false) {
            return $icon;
        }

        return '<i class="' . $this->escapeAttribute($icon) . '"></i>';
    }

    /**
     * Determine if an item should be expanded.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return bool
     */
    protected function isExpanded(MenuItem $item)
    {
        return $item->isActive() || $item->hasActiveOnChild();
    }

    /**
     * Format HTML attributes string.
     *
     * @param  string|null  $attributes
     * @return string
     */
    protected function formatAttributes($attributes)
    {
        $attributes = trim((string) $attributes);

        return $attributes === '' ? '' : ' ' . $attributes;
    }

    /**
     * Escape an HTML attribute value.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeAttribute($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
