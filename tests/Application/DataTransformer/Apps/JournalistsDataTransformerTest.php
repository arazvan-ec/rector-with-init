<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use App\Infrastructure\Service\Thumbor;
use Ec\Journalist\Domain\Model\Alias;
use Ec\Journalist\Domain\Model\Aliases;
use Ec\Journalist\Domain\Model\AliasId;
use Ec\Journalist\Domain\Model\Department;
use Ec\Journalist\Domain\Model\DepartmentId;
use Ec\Journalist\Domain\Model\Departments;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistId;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class JournalistsDataTransformerTest extends TestCase
{
    private JournalistsDataTransformer $transformer;
    private MockObject $thumbor;

    private string $aliasId;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->transformer = new JournalistsDataTransformer('dev', $this->thumbor);
        $this->aliasId = '20116';
    }

    #[Test]
    public function shouldInitialize(): void
    {
        $this->assertSame('dev', $this->transformer->extension());
    }

    #[Test]
    public function shouldWriteAndRead(): void
    {
        $journalistMock = $this->createMock(Journalist::class);
        $sectionMock = $this->createMock(Section::class);

        $this->transformer->write($this->aliasId, $journalistMock, $sectionMock, false);

        $result = $this->transformer->read();
        $this->assertEmpty($result);
    }

    #[Test]
    public function shouldTransformAJournalistWhenJournalistIsNotVisible(): void
    {
        $journalistId = '5164';
        $journalistName = 'Juan Carlos';
        $journalistUrl = '';
        $photoUrl = 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png';
        $twitter = 'elconfidencial';
        $privateAlias = false;

        $expectedThumbor = $photoUrl.'thumbor';

        $expectedAlias = [
            'id' => new AliasId($this->aliasId),
            'name' => $journalistName,
            'private' => false,
        ];

        $journalistMock = $this->createMock(Journalist::class);
        $sectionMock = $this->createMock(Section::class);
        $aliasesMock = $this->createMock(Aliases::class);
        $aliasIdMock = $this->createMock(AliasId::class);

        $journalistIdMock = $this->createMock(JournalistId::class);
        $departmentsMock = $this->createMock(Departments::class);

        $journalistMock->method('id')
            ->willReturn($journalistIdMock);

        $journalistMock->method('isVisible')
            ->willReturn(false);

        $journalistIdMock
            ->method('id')
            ->willReturn($journalistId);

        $journalistMock->method('aliases')
            ->willReturn($aliasesMock);

        $aliasItemMock = $this->createConfiguredMock(Alias::class, $expectedAlias);

        $aliasItemMock->expects(static::once())
            ->method('name')
            ->willReturn($journalistName);

        $aliasItemMock->method('id')
            ->willReturn($aliasIdMock);

        $aliasIdMock->method('id')
            ->willReturn($this->aliasId);

        $aliasItemMock->method('private')
            ->willReturn($privateAlias);

        $journalistMock->expects(static::once())
            ->method('departments')
            ->willReturn($departmentsMock);

        $journalistMock->expects(static::once())
            ->method('blogPhoto')
            ->willReturn('');

        $journalistMock->method('photo')
            ->willReturn($photoUrl);

        $journalistMock->method('twitter')
            ->willReturn($twitter);

        $this->thumbor->expects(static::once())
            ->method('createJournalistImage')
            ->with($photoUrl)
            ->willReturn($expectedThumbor);

        $bodyIterator = new \ArrayIterator([$aliasItemMock]);
        $aliasesMock
            ->method('rewind')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->rewind();
            });

        $aliasesMock
            ->method('current')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->current();
            });

        $aliasesMock
            ->method('key')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->key();
            });

        $aliasesMock
            ->method('next')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->next();
            });

        $aliasesMock
            ->method('valid')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->valid();
            });

        $result = $this->transformer
            ->write($this->aliasId, $journalistMock, $sectionMock, true)
            ->read();

        $expectedJournalist = [
            'journalistId' => $journalistId,
            'aliasId' => $this->aliasId,
            'name' => $journalistName,
            'url' => $journalistUrl,
            'photo' => $expectedThumbor,
            'departments' => [],
            'twitter' => '@'.$twitter,
            'private' => $privateAlias,
        ];

        $this->assertEquals($expectedJournalist['journalistId'], $result['journalistId']);
        $this->assertEquals($expectedJournalist['aliasId'], $result['aliasId']);
        $this->assertEquals($expectedJournalist['name'], $result['name']);
        $this->assertEquals($expectedJournalist['url'], $result['url']);
        $this->assertEquals($expectedJournalist['departments'], $result['departments']);
        $this->assertEquals($expectedJournalist['private'], $result['private']);
        $this->assertEquals(
            $expectedJournalist['photo'],
            $result['photo']
        );
        $this->assertEquals(
            $expectedJournalist['twitter'],
            $result['twitter']
        );

        $this->assertEquals($expectedJournalist, $result);
    }

    #[Test]
    public function writeMethodSetsProperties(): void
    {
        $journalistMock = $this->createMock(Journalist::class);
        $sectionMock = $this->createMock(Section::class);
        $aliasId = 'test-alias-id';
        $hasTwitter = true;

        $this->transformer->write($aliasId, $journalistMock, $sectionMock, $hasTwitter);

        $this->assertSame($aliasId, $this->getPrivateProperty($this->transformer, 'aliasId'));
        $this->assertSame($journalistMock, $this->getPrivateProperty($this->transformer, 'journalist'));
        $this->assertSame($sectionMock, $this->getPrivateProperty($this->transformer, 'section'));
        $this->assertSame($hasTwitter, $this->getPrivateProperty($this->transformer, 'hasTwitter'));
    }

    #[Test]
    public function shouldTransformAJournalist(): void
    {
        $journalistId = '5164';
        $journalistName = 'Juan Carlos';
        $journalistUrl = 'https://www.elconfidencial.dev/autores/juan-carlos-5164/';
        $photoUrl = 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png';
        $twitter = 'elconfidencial';
        $privateAlias = false;

        $expectedThumbor = $photoUrl.'thumbor';

        $expectedAlias = [
            'id' => new AliasId($this->aliasId),
            'name' => $journalistName,
            'private' => false,
        ];

        $journalistMock = $this->createMock(Journalist::class);
        $sectionMock = $this->createMock(Section::class);
        $aliasesMock = $this->createMock(Aliases::class);
        $aliasIdMock = $this->createMock(AliasId::class);

        $journalistIdMock = $this->createMock(JournalistId::class);
        $departmentsMock = $this->createMock(Departments::class);

        $journalistMock->method('id')
            ->willReturn($journalistIdMock);

        $journalistMock->method('isVisible')
            ->willReturn(true);

        $journalistIdMock
            ->method('id')
            ->willReturn($journalistId);

        $journalistMock->method('aliases')
            ->willReturn($aliasesMock);

        $aliasItemMock = $this->createConfiguredMock(Alias::class, $expectedAlias);

        $aliasItemMock->expects(static::once())
            ->method('name')
            ->willReturn($journalistName);

        $aliasItemMock->method('id')
            ->willReturn($aliasIdMock);

        $aliasIdMock->method('id')
            ->willReturn($this->aliasId);

        $aliasItemMock->method('private')
            ->willReturn($privateAlias);

        $journalistMock->expects(static::once())
            ->method('departments')
            ->willReturn($departmentsMock);

        $journalistMock->expects(static::once())
            ->method('name')
            ->willReturn($journalistName);

        $journalistMock->expects(static::once())
            ->method('blogPhoto')
            ->willReturn('');

        $journalistMock->method('photo')
            ->willReturn($photoUrl);

        $journalistMock->method('twitter')
            ->willReturn($twitter);

        $this->thumbor->expects(static::once())
            ->method('createJournalistImage')
            ->with($photoUrl)
            ->willReturn($expectedThumbor);

        $bodyIterator = new \ArrayIterator([$aliasItemMock]);
        $aliasesMock
            ->method('rewind')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->rewind();
            });

        $aliasesMock
            ->method('current')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->current();
            });

        $aliasesMock
            ->method('key')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->key();
            });

        $aliasesMock
            ->method('next')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->next();
            });

        $aliasesMock
            ->method('valid')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->valid();
            });

        $result = $this->transformer
            ->write($this->aliasId, $journalistMock, $sectionMock, true)
            ->read();

        $expectedJournalist = [
            'journalistId' => $journalistId,
            'aliasId' => $this->aliasId,
            'name' => $journalistName,
            'url' => $journalistUrl,
            'photo' => $expectedThumbor,
            'departments' => [],
            'twitter' => '@'.$twitter,
            'private' => $privateAlias,
        ];

        $this->assertEquals($expectedJournalist['journalistId'], $result['journalistId']);
        $this->assertEquals($expectedJournalist['aliasId'], $result['aliasId']);
        $this->assertEquals($expectedJournalist['name'], $result['name']);
        $this->assertEquals($expectedJournalist['url'], $result['url']);
        $this->assertEquals($expectedJournalist['departments'], $result['departments']);
        $this->assertEquals($expectedJournalist['private'], $result['private']);
        $this->assertEquals(
            $expectedJournalist['photo'],
            $result['photo']
        );
        $this->assertEquals(
            $expectedJournalist['twitter'],
            $result['twitter']
        );

        $this->assertEquals($expectedJournalist, $result);
    }

    #[Test]
    public function shouldTransformAJournalistWhenHasBlogPhoto(): void
    {
        $journalistId = '5164';
        $journalistName = 'Juan Carlos';
        $journalistUrl = 'https://www.elconfidencial.dev/autores/juan-carlos-5164/';
        $photoUrl = 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png';
        $expectedThumbor = $photoUrl.'thumbor';
        $departmentId = new DepartmentId('1');
        $departmentName = 'Técnico';
        $privateAlias = false;
        $expectedDepartment = [
            'id' => $departmentId,
            'name' => $departmentName,
        ];

        $expectedAlias = [
            'id' => new AliasId($this->aliasId),
            'name' => $journalistName,
            'private' => false,
        ];

        $journalistMock = $this->createMock(Journalist::class);
        $journalistIdMock = $this->createMock(JournalistId::class);

        $sectionMock = $this->createMock(Section::class);

        $aliasesMock = $this->createMock(Aliases::class);
        $aliasIdMock = $this->createMock(AliasId::class);

        $departmentsMock = $this->createMock(Departments::class);
        $departmentIdMock = $this->createMock(DepartmentId::class);

        $journalistMock->method('id')
            ->willReturn($journalistIdMock);

        $journalistMock->method('isVisible')
            ->willReturn(true);

        $journalistIdMock
            ->method('id')
            ->willReturn($journalistId);

        $journalistMock->method('aliases')
            ->willReturn($aliasesMock);

        $aliasItemMock = $this->createConfiguredMock(Alias::class, $expectedAlias);

        $aliasItemMock->expects(static::once())
            ->method('name')
            ->willReturn($journalistName);

        $aliasItemMock->method('id')
            ->willReturn($aliasIdMock);

        $aliasIdMock->method('id')
            ->willReturn($this->aliasId);

        $aliasItemMock->method('private')
            ->willReturn($privateAlias);

        $journalistMock->expects(static::once())
            ->method('departments')
            ->willReturn($departmentsMock);

        $journalistMock->expects(static::once())
            ->method('name')
            ->willReturn($journalistName);

        $journalistMock->expects(static::exactly(2))
            ->method('blogPhoto')
            ->willReturn($photoUrl);

        $this->thumbor->expects(static::once())
            ->method('createJournalistImage')
            ->with($photoUrl)
            ->willReturn($expectedThumbor);

        $bodyIterator = new \ArrayIterator([$aliasItemMock]);
        $aliasesMock
            ->method('rewind')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->rewind();
            });

        $aliasesMock
            ->method('current')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->current();
            });

        $aliasesMock
            ->method('key')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->key();
            });

        $aliasesMock
            ->method('next')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->next();
            });

        $aliasesMock
            ->method('valid')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->valid();
            });

        $departmentItemMock = $this->createConfiguredMock(Department::class, $expectedDepartment);
        $departmentItemMock->expects(static::once())
            ->method('id')
            ->willReturn($departmentId);

        $departmentIdMock->method('id')
            ->willReturn('1');

        $bodyIteratorDepartments = new \ArrayIterator([$departmentItemMock]);
        $departmentsMock
            ->method('rewind')
            ->willReturnCallback(static function () use ($bodyIteratorDepartments) {
                $bodyIteratorDepartments->rewind();
            });

        $departmentsMock
            ->method('current')
            ->willReturnCallback(static function () use ($bodyIteratorDepartments) {
                return $bodyIteratorDepartments->current();
            });

        $departmentsMock
            ->method('key')
            ->willReturnCallback(static function () use ($bodyIteratorDepartments) {
                return $bodyIteratorDepartments->key();
            });

        $departmentsMock
            ->method('next')
            ->willReturnCallback(static function () use ($bodyIteratorDepartments) {
                $bodyIteratorDepartments->next();
            });

        $departmentsMock
            ->method('valid')
            ->willReturnCallback(static function () use ($bodyIteratorDepartments) {
                return $bodyIteratorDepartments->valid();
            });

        $result = $this->transformer
            ->write($this->aliasId, $journalistMock, $sectionMock, true)
            ->read();

        $expectedJournalist = [
            'journalistId' => $journalistId,
            'aliasId' => $this->aliasId,
            'name' => $journalistName,
            'url' => $journalistUrl,
            'photo' => $expectedThumbor,
            'departments' => [
                $expectedDepartment,
            ],
            'private' => $privateAlias,
        ];

        $this->assertEquals($expectedJournalist['journalistId'], $result['journalistId']);
        $this->assertEquals($expectedJournalist['aliasId'], $result['aliasId']);
        $this->assertEquals($expectedJournalist['name'], $result['name']);
        $this->assertEquals($expectedJournalist['url'], $result['url']);
        $this->assertEquals($expectedJournalist['departments'], $result['departments']);
        $this->assertEquals($expectedJournalist['private'], $result['private']);
        $this->assertEquals(
            $expectedJournalist['photo'],
            $result['photo']
        );

        $this->assertEquals($expectedJournalist, $result);
    }

    #[Test]
    public function shouldReadTransformsJournalistData(): void
    {
        $aliasesMock = $this->createMock(Aliases::class);
        $journalistMock = $this->createMock(Journalist::class);
        $sectionMock = $this->createMock(Section::class);

        $journalistMock->method('aliases')
            ->willReturn($aliasesMock);

        $aliasesMock->method('hasAlias')
            ->willReturn(true);

        $journalistMock->expects(static::once())
            ->method('aliases')
            ->willReturn($aliasesMock);

        $this->transformer->write('test-alias-id', $journalistMock, $sectionMock, true);

        $this->transformer->read();
    }

    #[Test]
    public function shouldReturnJournalistUrlForPrivateAlias(): void
    {
        $siteId = 'elconfidencial';

        $journalistMock = $this->createMock(Journalist::class);
        $sectionMock = $this->createMock(Section::class);

        $sectionMock->method('siteId')
            ->willReturn($siteId);
        $sectionMock->method('getPath')
            ->willReturn('path');
        $sectionMock->method('isSubdomainBlog')
            ->willReturn(false);

        $this->transformer->write($this->aliasId, $journalistMock, $sectionMock, false);

        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('journalistUrl');

        /** @var string $result */
        $result = $method->invokeArgs($this->transformer, [$journalistMock]);
        $this->assertStringContainsString('https://www.elconfidencial.dev/autores/', $result);
    }

    #[Test]
    public function shouldReturnPhotoUrlWithPhoto(): void
    {
        $journalistMock = $this->createMock(Journalist::class);

        $journalistMock->expects(static::exactly(2))
            ->method('photo')
            ->willReturn('blog-photo.jpg');
        $this->thumbor->expects(static::once())
            ->method('createJournalistImage')
            ->willReturn('https://thumbor.example.com/blog-photo.jpg');

        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('photoUrl');

        $result = $method->invokeArgs($this->transformer, [$journalistMock]);
        $this->assertEquals('https://thumbor.example.com/blog-photo.jpg', $result);
    }

    #[Test]
    public function shouldReturnPhotoUrlWithBlogPhoto(): void
    {
        $blogPhoto = 'blog-photo.jpg';
        $journalistMock = $this->createMock(Journalist::class);

        $journalistMock->expects(static::exactly(2))
            ->method('blogPhoto')
            ->willReturn($blogPhoto);

        $this->thumbor->expects(static::once())
            ->method('createJournalistImage')
            ->willReturn('https://thumbor.example.com/blog-photo.jpg');

        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('photoUrl');

        $result = $method->invokeArgs($this->transformer, [$journalistMock]);
        $this->assertEquals('https://thumbor.example.com/blog-photo.jpg', $result);
    }

    #[Test]
    public function shouldReturnEmptyPhotoUrl(): void
    {
        $journalistMock = $this->createMock(Journalist::class);

        $journalistMock->expects(static::exactly(1))
            ->method('blogPhoto')
            ->willReturn('');
        $journalistMock->expects(static::exactly(1))
            ->method('photo')
            ->willReturn('');

        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('photoUrl');

        $result = $method->invokeArgs($this->transformer, [$journalistMock]);
        $this->assertEquals('', $result);
    }

    private function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);

        return $property->getValue($object);
    }
}
