<?php
/**
 * Mink test class for blended search.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace VuFindTest\Mink;

/**
 * Mink test class for blended search.
 *
 * @category VuFind
 * @package  Tests
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 * @retry    0
 */
class BlendedSearchTest extends \VuFindTest\Integration\MinkTestCase
{
    /**
     * Standard setup method.
     *
     * @return void
     */
    public function setUp(): void
    {
        // Give up if we're not running in CI:
        if (!$this->continuousIntegrationRunning()) {
            $this->markTestSkipped('Continuous integration not running.');
            return;
        }
    }

    /**
     * Get config settings for Blender.ini.
     *
     * @return array
     */
    protected function getBlenderIniOverrides()
    {
        return [
            'Backends' => [
                'Solr' => 'Items in Library',
                'SolrAuth' => 'Authors',
            ],
            'CheckboxFacets' => [
                'blender_backend:Solr' => 'Items in Library',
                'blender_backend:SolrAuth' => 'Authors',
            ],
            'General' => [
                'default_side_recommend[]'
                    => 'SideFacetsDeferred:Results:CheckboxFacets:Blender',
            ]
        ];
    }

    /**
     * Test disabled blended search
     *
     * @return void
     */
    public function testDisabledSearch()
    {
        $session = $this->getMinkSession();
        $session->visit($this->getVuFindUrl() . '/Search/Blended');
        $page = $session->getPage();
        $this->assertEquals(
            'An error has occurred',
            $this->findCss($page, '.alert-danger p')->getText()
        );
    }

    /**
     * Data provider for testSearch
     *
     * @return array
     */
    public function getSearchData(): array
    {
        $expected = array_fill(0, 20, 'Items in Library');

        $expectedFirstPage = $expected;
        $expectedFirstPage[2] = 'Authors';
        $expectedFirstPage[3] = 'Authors';
        $expectedFirstPage[7] = 'Authors';

        return [
            [
                ['page' => 1],
                $expectedFirstPage
            ],
            [
                ['page' => 2],
                $expected
            ]
        ];
    }

    /**
     * Test blended search
     *
     * @dataProvider getSearchData
     *
     * @return void
     */
    public function testSearch(array $queryParams, array $expectedLabels): void
    {
        $this->changeConfigs(
            ['Blender' => $this->getBlenderIniOverrides()],
            ['Blender']
        );

        $session = $this->getMinkSession();
        $session->visit($this->getVuFindUrl() . '/Search/Blended');
        $page = $session->getPage();

        $text = $this->findCss($page, '.search-stats strong')->getText();
        [$start, $limit] = explode(' - ', $text);
        $this->assertEquals(1, intval($start));
        $this->assertEquals(20, intval($limit));

        $i = 0;
        foreach ($this->findCss($page, '.result span.label-source') as $label) {
            $this->assertEquals($expectedLabels[$i], $label->getText(), $i);
            ++$i;
        }
    }

    /**
     * Test checkbox filters
     *
     * @return void
     */
    public function testSearchCheckboxFilter(): void
    {
        $this->changeConfigs(
            [
                'Blender' => $this->getBlenderIniOverrides(),
            ],
            ['Blender']
        );

        $session = $this->getMinkSession();
        $session->visit($this->getVuFindUrl() . '/Search/Blended');
        $page = $session->getPage();

        // Click first:
        $this->clickCss($page, 'a.checkbox-filter');
        $this->waitForPageLoad($page);
        foreach ($this->findCss($page, '.result span.label-source') as $label) {
            $this->assertEquals('Items in Library', $label->getText());
        }
        // Reset:
        $this->clickCss($page, 'a.checkbox-filter');
        $this->waitForPageLoad($page);
        // Click second:
        $this->clickCss($page, 'a.checkbox-filter', null, 1);
        $this->waitForPageLoad($page);
        $this->waitForPageLoad($page);
        foreach ($this->findCss($page, '.result span.label-source') as $label) {
            $this->assertEquals('Authors', $label->getText());
        }
    }
}
