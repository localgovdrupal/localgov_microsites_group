<?php

namespace Drupal\localgov_microsites_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\domain_group\DomainGroupResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for the Example module.
 */
class PreviewThemeController extends ControllerBase {
  /**
   * The Domain Group resolver.
   *
   * @var \Drupal\domain_group\DomainGroupResolverInterface
   */
  protected $domainGroupResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain_group_resolver')
    );
  }

  /**
   * Initialise a MicrositeAdminController instance.
   *
   * @param \Drupal\domain_group\DomainGroupResolverInterface $domain_group_resolver
   *   The domain group resolver service.
   */
  public function __construct(DomainGroupResolverInterface $domain_group_resolver) {
    $this->domainGroupResolver = $domain_group_resolver;
  }




  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function previewTheme() {
    $group_id = $this->domainGroupResolver->getActiveDomainGroupId();
    $url = Url::fromRoute('entity.group.canonical', ['group' => $group_id]);
    $url2 = $url->toString();
    return [
      '#markup' => '<div class="preview-theme layout">
      <h1>Theme preview page</h1>
      <p>This page reflects the css variables set in the active theme, or overriden through the UI.</p>

      <h2>Colours</h2>

      <h3>Primary colours</h3>
      <table>
        <tr>
          <th>Name</th>
          <th>Css variable</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>Primary colour</td>
          <td><code>--accent-1 </code></td>
          <td><div class="swatch accent-1"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Primary contrast colour</td>
          <td><code>--accent-1-contrast </code></td>
          <td><div class="swatch accent-contrast"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>

      <div class="swatch-wrapper"><span class="swatch-combined accent-1">Primary colour with text</span></div>

      <h3>Secondary colours</h3>
      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>Secondary colour</td>
          <td><div class="swatch accent-2"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Secondary colour contrast</td>
          <td><div class="swatch accent-2-contrast"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
      <div class="swatch-wrapper"><span class="swatch-combined accent-2">Secondary colour with text</span></div>

      <h3>Text colours</h3>
      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>Page background colour</td>
          <td><div class="swatch page-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Text colour</td>
          <td><div class="swatch text-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Link colour</td>
          <td><div class="swatch link-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-primary-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Heading 1 Font Colour</td>
          <td><div class="swatch heading-1-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-heading-1-font-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Heading 2 Font Colour</td>
          <td><div class="swatch heading-2-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-heading-2-font-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Heading 3 Font Colour</td>
          <td><div class="swatch heading-3-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-heading-3-font-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Heading 4 Font Colour</td>
          <td><div class="swatch heading-4-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-heading-4-font-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Heading 5 Font Colour</td>
          <td><div class="swatch heading-5-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-heading-5-font-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Heading 6 Font Colour</td>
          <td><div class="swatch heading-6-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-heading-6-font-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
      <div class="combined">
        <div class="swatch-wrapper"><span class="swatch-combined page-colour text-colour">Text on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour link-colour">Link on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour heading-1-colour">H1 on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour heading-2-colour">H2 on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour heading-3-colour">H3 on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour heading-4-colour">H4 on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour heading-5-colour">H5 on page</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour heading-6-colour">H6 on page</span></div>
      </div>
      <h3>All the colours</h3>
      <h4>Footer</h4>
      <p>The text, link and hover colours should all have enough contrast with the background.</p>

      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
          <th>Css variable</th>
        </tr>
        <tr>
          <td>Footer Background Colour</td>
          <td><div class="swatch footer-background-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-footer-background-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Footer Text Colour</td>
          <td><div class="swatch footer-text-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-footer-text-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Footer Link Colour</td>
          <td><div class="swatch footer-link-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-footer-link-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Footer Hover Colour</td>
          <td><div class="swatch footer-hover-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-footer-hover-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined footer-background-colour footer-text-colour">Footer background colour with text</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined footer-background-colour footer-link-colour">Footer background colour with link</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined footer-background-colour footer-hover-colour">Footer background colour with link hover</span></div>
        </div>
      <h4>Header</h4>
      <p>The text, link and hover colours should all have enough contrast with the background.</p>
      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>Header Background Colour</td>
          <td><div class="swatch header-background-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-header-bg-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Header Text Colour</td>
          <td><div class="swatch header-text-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-header-text-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Header Link Colour</td>
          <td><div class="swatch header-link-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-header-link-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>Header Hover Colour</td>
          <td><div class="swatch header-hover-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-header-hover-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined header-background-colour header-text-colour">header background colour with text</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined header-background-colour header-link-colour">header background colour with link</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined header-background-colour header-hover-colour">header background colour with link hover</span></div>
        </div>
      <h4>Off-canvas</h4>
      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>off-canvas Background Colour</td>
          <td><div class="swatch off-canvas-background-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-off-canvas-bg-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>off-canvas Text Colour</td>
          <td><div class="swatch off-canvas-text-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-off-canvas-text-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined off-canvas-background-colour off-canvas-text-colour">off-canvas background colour with text</span></div>
        </div>
      <h4>Pre-header</h4>
      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>pre-header Background Colour</td>
          <td><div class="swatch pre-header-background-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-pre-header-bg-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>pre-header Text Colour</td>
          <td><div class="swatch pre-header-text-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-pre-header-text-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>pre-header Link Colour</td>
          <td><div class="swatch pre-header-link-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-pre-header-link-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>pre-header Hover Colour</td>
          <td><div class="swatch pre-header-hover-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-pre-header-hover-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined pre-header-background-colour pre-header-text-colour">pre-header background colour with text</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined pre-header-background-colour pre-header-link-colour">pre-header background colour with link</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined pre-header-background-colour pre-header-hover-colour">pre-header background colour with link hover</span></div>
        </div>
      <h4>Submenu</h4>
      <table>
        <tr>
          <th>Name</th>
          <th>Swatch</th>
          <th>Edit link</th>
        </tr>
        <tr>
          <td>submenu Background Colour</td>
          <td><div class="swatch submenu-background-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-submenu-bg-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
        <tr>
          <td>submenu Text Colour</td>
          <td><div class="swatch submenu-text-colour"></div></td>
          <td><a href="'.$url2.'/edit#edit-lgms-submenu-text-colour-wrapper" target="_blank"> (edit)</a></td>
        </tr>
      </table>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined submenu-background-colour submenu-link-colour">submenu background colour with link</span></div>
      </div>
      <br />
      <hr />
      <h2>Typography</h2>
      <h1> Heading level 1</h1>
      <h2> Heading level 2</h2>
      <h3> Heading level 3</h3>
      <h4> Heading level 4</h4>
      <h5> Heading level 5</h5>
      <h6> Heading level 6</h6>
      <p>A paragraph of body text. Proin eget tortor risus. <a href="/">Quisque velit nisi</a>, pretium ut lacinia in, elementum id enim. Curabitur aliquet quam id dui posuere blandit. Pellentesque in ipsum id orci porta dapibus. Proin eget tortor risus. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Curabitur aliquet quam id dui posuere blandit. Pellentesque in ipsum id orci porta dapibus. Proin eget tortor risus. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Curabitur aliquet quam id dui posuere blandit. Pellentesque in ipsum id orci porta dapibus.</p>
      </div>',

    ];
  }
}
