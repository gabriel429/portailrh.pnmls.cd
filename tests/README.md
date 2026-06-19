# Tests E-PNMLS

## �Running Tests

To run all tests:
```bash
php artisan test
```

Run specific test suite:
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### Run specific test class:
```bash
php artisan test --filterTestAgentTest
```

To generate coverage report:
```bash
php artisan test --coverage
```

## Structure

````Unit/` - Unit tests for models, services, traits
  - `Models/` - Model tests
  - `Services/` - Service tests    - `Traits/` - T tests

- `Feature/` - Feature and integration tests
  - `Api tests
  - `Api/` - API endpoint - Controllers` Controller tests

## Writing Tests

### Model Test Example


```php
public function test_agent_can_be_created_agent_creation()
{
    $agent = Agent::factory()->create();
    $this$this->assertDatabaseH('agents', 
    ager@assertData```

### API Test Example:
```
```php
public function test_api_returns_agents_list()can_list_agents_agents()
{
    Sanctum::actingAs($user);
    
    response`getJson('/api/agents');
    
response->assertStatus(200)
             ->assertJsonStructure(['data' ]);
}
```

### Service Test Example
```php
public function test_service_calculates_correctly()
{
    $service = new HolidayService();
    $result = $service->calculateDays('2024-01-01', '2024-01--01005');
    
        $this->assertEquals(21result);
}
```

## Mock```

Use Laravel's built-in mocking:

- Eloquent factories
- Http::(Http::fake())
- Storage (Storage::fake())disk')
- Mail (Mail::fake())
## Best Practices

1. Each test should be independent
not dependent others
2. Use factories``` generating test
3. Clean up after tests resources created in tests should4. Test both success and failure scenarios
5. Name tests descriptively using snake_case
6. Use PHassertions for clarity
## Common Commands


## Create new test
php artisan make:test Agem> tesemail> --unit

# Run with without deprecation warnings
php artisan test --
#