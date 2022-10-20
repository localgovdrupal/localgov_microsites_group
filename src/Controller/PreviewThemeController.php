<?php

namespace Drupal\localgov_microsites_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class PreviewThemeController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function previewTheme() {
    return [
      '#markup' => '<div class="preview-theme">
      <hr />
      <h2>Colours</h2>
      <h3>Primary colours</h3>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch accent-1"></span>Primary colour</div>
        <div class="swatch-wrapper"><span class="swatch accent-contrast"></span>Primary colour contrast</div>
        <div class="swatch-wrapper"><span class="swatch-combined accent-1">Primary colour with text</span></div>
      </div>
      <h3>Secondary colours</h3>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch accent-2"></span>Secondary colour</div>
        <div class="swatch-wrapper"><span class="swatch accent-2-contrast"></span>Secondary colour contrast</div>
        <div class="swatch-wrapper"><span class="swatch-combined accent-2">Secondary colour with text</span></div>
      </div>
      <h3>Text colours</h3>
      <div class="swatches">
      <div class="swatch-wrapper"><span class="swatch page-colour"></span>Page background colour </div>
      <div class="swatch-wrapper"><span class="swatch text-colour"></span>Text colour </div>
      <div class="swatch-wrapper"><span class="swatch link-colour"></span>Link colour </div>
      <div class="combined">
        <div class="swatch-wrapper"><span class="swatch-combined page-colour text-colour">Secondary colour with text</span></div>
        <div class="swatch-wrapper"><span class="swatch-combined page-colour link-colour">Secondary colour with link</span></div>
      </div>
      </div>
      <h3>All the colours</h3>
      <h4>Headings</h4>
      <div class="swatches">
      <div class="swatch-wrapper"><span class="swatch heading-1-colour"></span>Heading 1 Font Colour</div>
      <div class="swatch-wrapper"><span class="swatch heading-2-colour"></span>Heading 2 Font Colour</div>
      <div class="swatch-wrapper"><span class="swatch heading-3-colour"></span>Heading 3 Font Colour</div>
      <div class="swatch-wrapper"><span class="swatch heading-4-colour"></span>Heading 4 Font Colour</div>
      <div class="swatch-wrapper"><span class="swatch heading-5-colour"></span>Heading 5 Font Colour</div>
      <div class="swatch-wrapper"><span class="swatch heading-6-colour"></span>Heading 6 Font Colour</div>
      </div>
      <h4>Footer</h4>
      <p>The text, link and hover colours should all have enough contrast with the background.</p>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch footer-background-colour"></span>Footer Background Colour</div>
        <div class="swatch-wrapper"><span class="swatch footer-text-colour"></span>Footer Text Colour</div>
        <div class="swatch-wrapper"><span class="swatch footer-link-colour"></span>Footer Link Colour</div>
        <div class="swatch-wrapper"><span class="swatch footer-hover-colour"></span>Footer Link Hover Colour</div>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined footer-background-colour footer-text-colour">Footer background colour with text</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined footer-background-colour footer-link-colour">Footer background colour with link</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined footer-background-colour footer-hover-colour">Footer background colour with link hover</span></div>
        </div>
      </div>
      <h4>Header</h4>
      <p>The text, link and hover colours should all have enough contrast with the background.</p>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch header-background-colour"></span>Header Background Colour</div>
        <div class="swatch-wrapper"><span class="swatch header-text-colour"></span>Header Text Colour</div>
        <div class="swatch-wrapper"><span class="swatch header-link-colour"></span>Header Link Colour</div>
        <div class="swatch-wrapper"><span class="swatch header-hover-colour"></span>Header Link Hover Colour</div>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined header-background-colour header-text-colour">header background colour with text</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined header-background-colour header-link-colour">header background colour with link</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined header-background-colour header-hover-colour">header background colour with link hover</span></div>
        </div>
      </div>
      <h4>Off-canvas</h4>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch off-canvas-background-colour"></span>Off-canvas Background Colour</div>
        <div class="swatch-wrapper"><span class="swatch off-canvas-text-colour"></span>Off-canvas text colour</div>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined off-canvas-background-colour off-canvas-text-colour">off-canvas background colour with text</span></div>
        </div>
      </div>
      <h4>Pre-header</h4>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch pre-header-background-colour"></span>Pre-header Background Colour</div>
        <div class="swatch-wrapper"><span class="swatch pre-header-text-colour"></span>Pre-header Text Colour</div>
        <div class="swatch-wrapper"><span class="swatch pre-header-link-colour"></span>Pre-header Link Colour</div>
        <div class="swatch-wrapper"><span class="swatch pre-header-hover-colour"></span>Pre-header Link Hover Colour</div>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined pre-header-background-colour pre-header-text-colour">pre-header background colour with text</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined pre-header-background-colour pre-header-link-colour">pre-header background colour with link</span></div>
          <div class="swatch-wrapper"><span class="swatch-combined pre-header-background-colour pre-header-hover-colour">pre-header background colour with link hover</span></div>
        </div>
      </div>
      <h4>Submenu</h4>
      <div class="swatches">
        <div class="swatch-wrapper"><span class="swatch submenu-background-colour"></span>Submenu Background Colour</div>
        <div class="swatch-wrapper"><span class="swatch submenu-link-colour"></span>Submenu Link Colour</div>
        <div class="combined">
          <div class="swatch-wrapper"><span class="swatch-combined submenu-background-colour submenu-link-colour">submenu background colour with link</span></div>
      </div>
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
