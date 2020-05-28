<?php

namespace Tests\Unit\Services;

use Mockery;
use PHPUnit\Framework\TestCase;
use App\Services\ZendeskService;
use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Support\Facades\Cache;

class ZendeskServiceTest extends TestCase
{
    public function setUp() : void {
        $this->cache = Cache::shouldReceive('remember');
    }
    
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    /**
     * @group zendeskService
     * @group getUsersByKey
     * @runInSeparateProcess
     * Tests the api edit form
     */
    public function test_getUsersByKey_is_filterable() {
        $mockCacheResponse = [
            (object) ['id' => 1, "name" => "users_name_a"],
            (object) ['id' => 2, "name" => "users_name_b"],
            (object) ['id' => 3, "name" => "users_name_c"]
        ];
        $this->cache
                ->once()
                ->andReturn($mockCacheResponse);

        $zendesk = new ZendeskService();

        $users = $zendesk->getUsersByKey();
        $filteredOneUsers = $zendesk->filterUsers(["1"])->getUsersByKey();
        $filteredTwoUsers = $zendesk->filterUsers(["1", "2"])->getUsersByKey();
        $allUsers = $zendesk->filterUsers(["*"])->getUsersByKey();
        $allUsersInHaystack = $zendesk->filterUsers(["*", "1"])->getUsersByKey();
        $allUsersString = $zendesk->filterUsers("*")->getUsersByKey();
        $noUsers = $zendesk->filterUsers([])->getUsersByKey();
        $noUserNotFound = $zendesk->filterUsers(["4"])->getUsersByKey();
        $noUsersNotFound = $zendesk->filterUsers(["9", "10"])->getUsersByKey();

        $this->assertCount(3, $users);
        $this->assertCount(1, $filteredOneUsers);
        $this->assertCount(2, $filteredTwoUsers);
        $this->assertCount(3, $allUsers);
        $this->assertCount(3, $allUsersInHaystack);
        $this->assertCount(3, $allUsersString);
        $this->assertCount(0, $noUsers);
        $this->assertCount(0, $noUserNotFound);
        $this->assertCount(0, $noUsersNotFound);
        $this->assertObjectHasAttribute("id", $users[1]);
        $this->assertObjectHasAttribute("id", collect($users)->get(2));
        $this->assertObjectHasAttribute("id", $users[3]);
        $this->assertNull(collect($users)->get(4));
    }

    /**
     * @group zendeskService
     * @group getGroupsByKey
     * @runInSeparateProcess
     * Tests the api edit form
     */
    public function test_getGroupsByKey_is_filterable() {
        $mockCacheResponse = [
            (object) ['id' => 1, "name" => "group_name_a"],
            (object) ['id' => 2, "name" => "group_name_b"],
            (object) ['id' => 3, "name" => "group_name_c"]
        ];
        $this->cache
                ->once()
                ->andReturn($mockCacheResponse);

        $zendesk = new ZendeskService();

        $Groups = $zendesk->getGroupsByKey();
        $filteredOneGroups = $zendesk->filterGroups(["1"])->getGroupsByKey();
        $filteredTwoGroups = $zendesk->filterGroups(["1", "2"])->getGroupsByKey();
        $allGroups = $zendesk->filterGroups(["*"])->getGroupsByKey();
        $allGroupsInHaystack = $zendesk->filterGroups(["*", "1"])->getGroupsByKey();
        $allGroupsString = $zendesk->filterGroups("*")->getGroupsByKey();
        $noGroups = $zendesk->filterGroups([])->getGroupsByKey();
        $noUserNotFound = $zendesk->filterGroups(["4"])->getGroupsByKey();
        $noGroups = $zendesk->filterGroups(["9", "10"])->getGroupsByKey();

        $this->assertCount(3, $Groups);
        $this->assertCount(1, $filteredOneGroups);
        $this->assertCount(2, $filteredTwoGroups);
        $this->assertCount(3, $allGroups);
        $this->assertCount(3, $allGroupsInHaystack);
        $this->assertCount(3, $allGroupsString);
        $this->assertCount(0, $noGroups);
        $this->assertCount(0, $noUserNotFound);
        $this->assertCount(0, $noGroups);
        $this->assertObjectHasAttribute("id", $Groups[1]);
        $this->assertObjectHasAttribute("id", collect($Groups)->get(2));
        $this->assertObjectHasAttribute("id", $Groups[3]);
        $this->assertNull(collect($Groups)->get(4));
    }
    
    /**
     * @group zendeskService
     * @group getCustomFieldsByValue
     * @runInSeparateProcess
     * Tests the api edit form
     */
    public function test_getCustomFieldsByValue_is_filterable() {
        $mockCacheResponse = [
            (object) ["id" => 11, 'value' => "a", "name" => "name_a"],
            (object) ["id" => 22, 'value' => "b", "name" => "name_b"],
            (object) ["id" => 33, 'value' => "c", "name" => "name_c"]
        ];

        $this->cache
                ->once()
                ->andReturn($mockCacheResponse);

        $zendesk = new ZendeskService();

        $CustomFields = $zendesk->getCustomFieldsByValue();
        $filteredOneCustomFields = $zendesk->filterCustomFields(["a"])->getCustomFieldsByValue();
        $filteredTwoCustomFields = $zendesk->filterCustomFields(["a", "b"])->getCustomFieldsByValue();
        $allCustomFields = $zendesk->filterCustomFields(["*"])->getCustomFieldsByValue();
        $allCustomFieldsInHaystack = $zendesk->filterCustomFields(["*", "a"])->getCustomFieldsByValue();
        $allCustomFieldsString = $zendesk->filterCustomFields("*")->getCustomFieldsByValue();
        $noCustomFields = $zendesk->filterCustomFields([])->getCustomFieldsByValue();
        $nullFilter = $zendesk->filterCustomFields(null)->getCustomFieldsByValue();
        $noUserNotFound = $zendesk->filterCustomFields(["d"])->getCustomFieldsByValue();
        $noCustomFields = $zendesk->filterCustomFields(["y", "z"])->getCustomFieldsByValue();

        $this->assertCount(3, $CustomFields);
        $this->assertCount(1, $filteredOneCustomFields);
        $this->assertCount(2, $filteredTwoCustomFields);
        $this->assertCount(3, $allCustomFields);
        $this->assertCount(3, $allCustomFieldsInHaystack);
        $this->assertCount(3, $allCustomFieldsString);
        $this->assertCount(0, $noCustomFields);
        $this->assertCount(0, $nullFilter);
        $this->assertCount(0, $noUserNotFound);
        $this->assertCount(0, $noCustomFields);
        $this->assertObjectHasAttribute("value", $CustomFields["a"]);
        $this->assertObjectHasAttribute("value", collect($CustomFields)->get("b"));
        $this->assertObjectHasAttribute("value", $CustomFields["c"]);
        $this->assertNull(collect($CustomFields)->get("y"));
    }        
}
