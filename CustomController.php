<?php

namespace dokuwiki\template\notos;

use dokuwiki\Menu\Item\Login;
use dokuwiki\Menu\MenuInterface;
use dokuwiki\template\twigstarter\CustomControllerInterface;
use dokuwiki\template\twigstarter\TemplateController;

/**
 * Notos custom methods
 */
class CustomController implements CustomControllerInterface
{
    protected $tpl;

    /** @inheritDoc */
    public function __construct(TemplateController $tpl)
    {
        $this->tpl = $tpl;
    }

    /**
     * Renders the navigational list items
     *
     * We only support two levels. Deeper levels that might be returned from the navigation
     * control page will simply be ignored here
     *
     * @param string $class additional classes to add to the navigation
     * @return string
     */
    public function renderNavigation($class = '')
    {
        global $ID;
        global $ACT;
        $html = '';

        if ($ACT !== 'show') return $html;

        $controlPage = tpl_getConf('navigation_page');
        if (!page_exists($controlPage)) return $html;

        $pages = $this->parseNavigation(wikiFN($controlPage));

        $html .= '<ul class="navtabs ' . $class . '">';
        foreach ($pages as $page) {
            if ($this->isActive($page['page'], $ID)) {
                $active = ' active';
            } else {
                $active = '';
            }

            $html .= '<li class="primary' . $active . '">';
            $html .= $this->navItemHTML($page);
            $html .= '<span class="opener">' . inlineSVG(__DIR__ . '/ico/plus-circle-outline.svg') . '</span>';
            $html .= '</li>';

            // second level is a second item, because we reorder with flex box later
            if (isset($page['sub'])) {
                $html .= '<li class="secondary' . $active . '">';
                $html .= '<ul>';
                foreach ($page['sub'] as $subpage) {
                    $html .= '<li>';
                    $html .= $this->navItemHTML($subpage);
                    $html .= '</li>';
                }
                $html .= '</ul>';
                $html .= '</li>';
            }
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Print a single Navigation Item
     *
     * @param $item
     * @return string
     */
    protected function navItemHTML($item)
    {
        if ($item['type'] == 'internal') {
            return html_wikilink($item['page'], $item['title']);
        }
        $attr = buildAttributes([
            'href' => $item['page'],
            'title' => $item['page'],
            'class' => 'external',
        ]);
        return "<a $attr>" . hsc($item['title']) . '</a>';
    }

    /**
     * Is the current parent page "active" depending on the second one?
     *
     * @param string $parent
     * @param string $page
     * @return bool
     * @todo this should have tests
     */
    protected function isActive($parent, $page)
    {
        if ($parent === $page) return true;
        $parentNS = explode(':', $parent);
        array_pop($parentNS);
        $pageParts = explode(':', $page);
        $pageParts = array_splice($pageParts, 0, count($parentNS));

        $parent = join(':', $parentNS);
        $page = join(':', $pageParts);

        if ($parent === $page) return true;
        return false;
    }

    /**
     * Parses the given page for a nested, unordered list
     *
     * Returns the list as nested array
     *
     * @param string $controlPageFile
     * @return array
     */
    public function parseNavigation($controlPageFile)
    {
        $instructions = p_cached_instructions($controlPageFile);

        $result = [];
        $pointers = [&$result]; // point to current list
        $pidx = 0; // index of last pointer

        foreach ($instructions as $instruction) {
            switch ($instruction[0]) {
                case 'listu_open':
                    // find index of last item in current list
                    $eidx = count($pointers[$pidx]) - 1;
                    // open a new list in the last item of the current list
                    $pointers[$pidx][$eidx]['sub'] = [];
                    // store that new list in the pointer stack
                    $pointers[] = &$pointers[$pidx][$eidx]['sub'];
                    // increase pointer index
                    $pidx++;
                    break;
                case 'listu_close':
                    // close a list
                    array_pop($pointers);
                    $pidx--;
                    break;
                case 'internallink':
                    // resolve ID
                    global $ID;
                    $resolver = new \dokuwiki\File\PageResolver($ID);
                    $page = $resolver->resolveId($instruction[1][0]);
                    // append to current list
                    $pointers[$pidx][] = [
                        'type' => 'internal',
                        'page' => $page,
                        'title' => $instruction[1][1],
                    ];
                    break;
                case 'externallink':
                    // append to current list
                    $pointers[$pidx][] = [
                        'type' => 'external',
                        'page' => $instruction[1][0],
                        'title' => $instruction[1][1],
                    ];
                    break;
            }
        }

        // clean up by moving everything up into a root array
        if (isset($result[-1])) {
            $result = $result[-1]['sub'];
        }

        return $result;
    }

    /**
     * Initializes and returns one of the menus
     *
     * @param string $type
     * @return MenuInterface
     */
    public function menu($type)
    {
        $class = '\\dokuwiki\\Menu\\' . ucfirst($type) . 'Menu';
        if (class_exists($class)) {
            return new $class();
        } else {
            return new NotosMenu($type);
        }
    }

    /**
     * Get Login/Logout Button
     * @return string
     */
    public function loginButton()
    {
        try {
            return (new Login())->asHtmlLink();
        } catch (\RuntimeException $ignored) {
            // item not available
            return '';
        }
    }
}
