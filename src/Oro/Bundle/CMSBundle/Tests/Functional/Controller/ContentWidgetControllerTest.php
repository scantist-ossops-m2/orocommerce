<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentWidgetControllerTest extends WebTestCase
{
    /** @var string */
    private const WIDGET_NAME = 'test widget';
    private const WIDGET_TYPE = 'custom widget type';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @return ContentWidget
     */
    public function testCreate(): ContentWidget
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_widget_create'));

        $form = $crawler->selectButton('Save')->form();
        $form['oro_cms_content_widget[name]'] = self::WIDGET_NAME;
        $form['oro_cms_content_widget[widgetType]'] = self::WIDGET_TYPE;

        $this->client->followRedirects();

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Content widget has been saved', $crawler->html());

        $contentWidget = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ContentWidget::class)
            ->findOneBy(['name' => self::WIDGET_NAME]);

        $this->assertInstanceOf(ContentWidget::class, $contentWidget);
        $this->assertEquals(self::WIDGET_TYPE, $contentWidget->getWidgetType());

        return $contentWidget;
    }

    /**
     * @depends testCreate
     *
     * @param ContentWidget $contentWidget
     * @return ContentWidget
     */
    public function testUpdate(ContentWidget $contentWidget): ContentWidget
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_widget_update', ['id' => $contentWidget->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_cms_content_widget[name]'] = self::WIDGET_NAME . ' updated';

        $this->client->followRedirects();

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Content widget has been saved', $crawler->html());

        $contentWidget = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ContentWidget::class)
            ->findOneBy(['name' => self::WIDGET_NAME . ' updated']);

        $this->assertInstanceOf(ContentWidget::class, $contentWidget);
        $this->assertEquals(self::WIDGET_TYPE, $contentWidget->getWidgetType());

        return $contentWidget;
    }

    /**
     * @depends testUpdate
     *
     * @param ContentWidget $contentWidget
     * @return ContentWidget
     */
    public function testView(ContentWidget $contentWidget): ContentWidget
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_widget_view', ['id' => $contentWidget->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($contentWidget->getName(), $crawler->html());
        $this->assertContains($contentWidget->getWidgetType(), $crawler->html());

        return $contentWidget;
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_widget_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('cms-content-widget-grid', $crawler->html());
        $this->assertContains('Create Content Widget', $crawler->filter('div.title-buttons-container')->html());
    }

    /**
     * @depends testView
     *
     * @param ContentWidget $contentWidget
     */
    public function testGrid(ContentWidget $contentWidget): void
    {
        $response = $this->client->requestGrid('cms-content-widget-grid');

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);

        $data = reset($result['data']);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($contentWidget->getId(), $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertEquals($contentWidget->getName(), $data['name']);
        $this->assertArrayHasKey('widgetType', $data);
        $this->assertEquals($contentWidget->getWidgetType(), $data['widgetType']);
    }
}
