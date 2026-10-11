# Ollama LLM Integration Skeleton

**Status**: Skeleton phase (dormant until Ollama container ready)  
**Timeline**: Weeks to months away from full deployment  
**Effort Now**: 2-3 days for skeleton architecture  
**Effort Later**: 1-2 weeks to wire full implementation  

---

## Why Ollama Changes Everything

### Before (Cloud LLM)
- **Cost**: $0.01-0.10 per transaction (adds up!)
- **Latency**: 500ms-2s per request (kills UI responsiveness)
- **Dependency**: Internet + API service availability
- **Privacy**: Your transaction data leaves the server
- **Decision**: Skip LLM integration, too expensive

### After (Self-Hosted Ollama)
- **Cost**: Zero API costs (just server CPU)
- **Latency**: 1-3s acceptable for "Finding best match..." UI (non-blocking)
- **Dependency**: Local HTTP service (controlled)
- **Privacy**: Data never leaves your infrastructure
- **Decision**: Yes, include as Tier 5 skeleton now, wire later

---

## Architecture: Stubs for Future Implementation

### Phase 1 (Now): Skeleton Only
```php
// app/Services/LLM/OllamaMatchResolver.php

namespace app\Services\LLM;

class OllamaMatchResolver
{
    private $ollamaUrl = 'http://localhost:11434';
    private $model = 'mistral';  // Or llama2, neural-chat
    private $enabled = false;    // Stays false until Ollama ready
    
    public function __construct(array $config = [])
    {
        $this->ollamaUrl = $config['ollama_url'] ?? $this->ollamaUrl;
        $this->model = $config['model'] ?? $this->model;
        $this->enabled = $config['enabled'] ?? false;  // Disabled by default
    }
    
    /**
     * When multi-factor score is ambiguous (< 75%), use LLM for tie-breaking
     * 
     * Input: Transaction + Top 3 ranked candidates from multi-factor scoring
     * Output: Best match explanation + confidence (semantic reasoning)
     * 
     * @param array $transaction Transaction data (description, amount, account, etc)
     * @param array $candidates Top 3 ranked candidates: [
     *     ['id' => 'QE#2', 'multifactor_score' => 72, 'pattern' => 'Group Benefit', ...],
     *     ['id' => 'QE#12', 'multifactor_score' => 45, 'pattern' => 'Interest', ...],
     *     ['id' => 'ABC', 'multifactor_score' => 38, 'pattern' => 'Customer ABC', ...]
     * ]
     * @return array ['match_id' => 'QE#2', 'confidence' => 92, 'reasoning' => '...']
     */
    public function resolve(array $transaction, array $candidates): array
    {
        if (!$this->enabled) {
            return ['match_id' => null, 'confidence' => 0, 'reasoning' => 'LLM disabled'];
        }
        
        try {
            $prompt = $this->buildPrompt($transaction, $candidates);
            $response = $this->callOllama($prompt);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            // Graceful degradation: fall back to multi-factor top match
            \error_log("Ollama error: {$e->getMessage()}");
            return ['match_id' => null, 'confidence' => 0, 'reasoning' => 'LLM unavailable'];
        }
    }
    
    private function buildPrompt(array $transaction, array $candidates): string
    {
        // TODO: Design the prompt that gives Ollama context
        // Should include:
        // - Transaction details (description, amount, account, timestamp)
        // - Candidate match patterns (what each pattern is, when last seen)
        // - Explicit ask: "Which candidate is most semantically similar?"
        
        return <<<PROMPT
You are a bank transaction matching AI. Your job is to identify which pattern a transaction best matches.

Transaction: {$transaction['description']} ({$transaction['amount']} from account {$transaction['account_id']})

Candidates:
PROMPT;
    }
    
    private function callOllama(string $prompt): string
    {
        // TODO: Implement HTTP call to Ollama when container is ready
        // Use: GET /api/generate with model + prompt
        // Handle: streaming response vs complete response
        
        throw new \Exception('Ollama integration not yet implemented');
    }
    
    private function parseResponse(string $response): array
    {
        // TODO: Parse Ollama response into structured format
        // Extract: best match ID, confidence score, reasoning
        // Handle: parsing errors, unexpected formats
        
        return [
            'match_id' => null,
            'confidence' => 0,
            'reasoning' => 'Parse not implemented'
        ];
    }
    
    /**
     * Health check: Is Ollama container running and ready?
     */
    public function isHealthy(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $response = @json_decode(
                file_get_contents("{$this->ollamaUrl}/api/tags"),
                true
            );
            return isset($response['models']) && count($response['models']) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Load specified model into memory (warm up), or list available models
     */
    public function listModels(): array
    {
        try {
            $response = json_decode(
                file_get_contents("{$this->ollamaUrl}/api/tags"),
                true
            );
            return $response['models'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
```

### Phase 2 (Now): Integration Point in PatternMatcher
```php
// app/Services/PatternMatcher.php (sketch)

class PatternMatcher
{
    private $scoringEngine;
    private $ollamaResolver;  // Add: dependency injection
    private $ollamaThreshold = 0.75;  // Below 75% → ask Ollama
    
    public function __construct(
        ScoringEngine $scoringEngine,
        OllamaMatchResolver $ollamaResolver = null
    ) {
        $this->scoringEngine = $scoringEngine;
        $this->ollamaResolver = $ollamaResolver;  // Optional
    }
    
    public function search(array $transaction): array
    {
        // Step 1: Multi-factor scoring (always runs)
        $candidates = $this->scoringEngine->scoreAll($transaction);
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        
        $topMatch = $candidates[0];
        
        // Step 2: If below threshold and Ollama available → get semantic opinion
        if ($topMatch['score'] < $this->ollamaThreshold && $this->ollamaResolver) {
            $ollamaResult = $this->ollamaResolver->resolve($transaction, array_slice($candidates, 0, 3));
            
            if ($ollamaResult['match_id']) {
                // Enhance top match with LLM confidence + reasoning
                return [
                    [
                        ...array_values(array_filter($candidates, 
                            fn($c) => $c['id'] === $ollamaResult['match_id']
                        ))[0] ?? $candidates[0],
                        'llm_confidence' => $ollamaResult['confidence'],
                        'llm_reasoning' => $ollamaResult['reasoning'],
                        'score' => ($topMatch['score'] + ($ollamaResult['confidence'] / 100)) / 2
                    ]
                ] + array_slice($candidates, 1);
            }
        }
        
        return $candidates;
    }
}
```

### Phase 3 (Now): Configuration for Easy Enable/Disable
```php
// config/services.php (or equivalent config)

return [
    'ollama' => [
        'enabled' => env('OLLAMA_ENABLED', false),  // Default disabled
        'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'mistral'),
        'threshold' => env('OLLAMA_THRESHOLD', 0.75),  // Below 75% confidence → ask Ollama
        'timeout' => env('OLLAMA_TIMEOUT', 5000),  // 5 second timeout
        'cache_minutes' => env('OLLAMA_CACHE', 60),  // Cache LLM responses 1 hour
    ]
];

// .env example
OLLAMA_ENABLED=false
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=mistral
OLLAMA_THRESHOLD=0.75
OLLAMA_TIMEOUT=5000
```

---

## What Happens When Ollama Container Comes Online

### Step 1: Add Ollama Health Check to Admin Dashboard
```php
// Show status: "Ollama: [Green] Ready" or "[Red] Not responding" or "[Yellow] Disabled"
// Show model loaded: "Mistral 7B - Ready"
// Show health check result in log
```

### Step 2: Implement HTTP Client
```php
// In OllamaMatchResolver::callOllama()
$client = new \GuzzleHttp\Client();
$response = $client->post("{$this->ollamaUrl}/api/generate", [
    'json' => [
        'model' => $this->model,
        'prompt' => $prompt,
        'stream' => false,
        'temperature' => 0.3,  // Low: factual, not creative
    ],
    'timeout' => 5,
    'connect_timeout' => 2,
]);

return $response->getBody()->getContents();
```

### Step 3: Wire UI to Show LLM Reasoning
```html
<!-- When auto-selecting ambiguous match -->
<div class="match-result">
    <strong>Match: {{ match.label }}</strong>
    
    <!-- Multi-factor score only -->
    <p>Confidence (algorithmic): {{ match.score }}%</p>
    
    <!-- Add: LLM reasoning if available -->
    @if($match['llm_reasoning'])
        <p class="llm-explanation">
            <em>LLM reasoning: {{ $match['llm_reasoning'] }}</em>
        </p>
    @endif
</div>
```

### Step 4: Test & Tune
- Enable in staging environment first
- Test with 20-30 transactions
- Verify parse accuracy (Ollama's reasoning format)
- Tune prompt if parsing fails
- Monitor latency (should be <3s)
- Then enable in production with `OLLAMA_ENABLED=true`

---

## Prompt Design (For Later Implementation)

```
System Prompt (sent once):
You are a bank transaction classification expert. Your role is to identify which of 
several pre-defined patterns a financial transaction best matches.

Each pattern represents a common transaction type your user sees regularly. Your 
job is semantic reasoning: which pattern does this transaction SEMANTICALLY belong to?

Context:
- Patterns have associated keywords and examples
- Higher recency_score means this pattern was seen recently
- New patterns have lower occurrence_count

User Prompt (per transaction):
Transaction: "Pre-Auth Debit; Group Benefit 2025 - Your Company Inc"
Amount: $1,200.00
Account: Visa ending 4567
Historical: Similar transactions 2 weeks ago matched to "Group Benefit Insurance"

Candidates:
1. Pattern "Group Benefit Insurance" (QE#2)
   - Examples: "Group Benefit", "Insurance Premium", "Company Benefits"
   - Last seen: 2 weeks ago
   - Recency score: 0.9
   - Occurrence: 12 times

2. Pattern "Interest Earned" (QE#12)
   - Examples: "Interest Paid", "Interest Accrued"
   - Last seen: 1 month ago
   - Recency score: 0.7
   - Occurrence: 5 times

3. Patient "Manual Expense Coding" (ABC)
   - Manual entry, no pre-existing pattern
   - Confidence: 0.3

Question: Which pattern is this transaction most likely to represent?
Your reasoning should be grounded in:
- Semantic similarity to pattern keywords
- Recency (has this pattern appeared recently?)
- Consistency (is this consistent with how you've seen this pattern before?)

Return: {match: "QE#2", confidence: 92, reasoning: "..."}
```

---

## Why Skeleton Now, Full Implementation Later?

### Benefits of Skeleton Phase (Now)
- ✅ Architecture is LLM-ready (not retrofitted later)
- ✅ Config management prepared (easy enable/disable)
- ✅ No breaking changes when LLM comes online
- ✅ Team aware of coming enhancement (manages expectations)
- ✅ Code review catches design issues early
- ✅ When Ollama ready: just fill in 3-4 methods, enable in config

### Why Not Full Implementation Now?
- ❌ Ollama container not ready (weeks to months)
- ❌ No way to test HTTP integration (no endpoint to test)
- ❌ No data yet on Ollama response format (will change prompting)
- ❌ Model selection not finalized (Mistral? Llama2? TinyLlama?)
- ❌ Server resources TBD (GPU? RAM requirements?)
- ❌ Wasted effort if Ollama timeline extends

---

## Implementation Checklist

### Phase 1A: Skeleton Architecture (Before Tier 1 deployment)
- [ ] Create `OllamaMatchResolver` class with stubs
- [ ] Add dependency injection to `PatternMatcher`
- [ ] Create `config/services.php` for Ollama settings
- [ ] Add health check endpoint to API
- [ ] Document stubs with TODOs for Phase 2
- [ ] Add to git with `OLLAMA_ENABLED=false` by default
- [ ] Code review + merge to `hotfix/customer-match`

### Phase 1B: Ready for Ollama (When Container Onlines)
- [ ] Install Ollama, choose model (Mistral / Llama2 / Neural-Chat)
- [ ] Test model locally with sample transaction prompts
- [ ] Implement `callOllama()` HTTP integration
- [ ] Implement `parseResponse()` for response format
- [ ] Refine prompt in `buildPrompt()` based on model output
- [ ] Test with 20-30 transactions in staging
- [ ] Verify latency acceptable (<3s, non-blocking UI)
- [ ] Enable `OLLAMA_ENABLED=true` in production

### Phase 2: Optimize (After 200+ Transactions)
- [ ] Analyze Ollama response quality
- [ ] Tune prompt based on mismatches
- [ ] Consider response caching (same transaction description → cached LLM answer)
- [ ] Monitor server resource usage (CPU, latency under load)
- [ ] Experiment with different models if accuracy weak

---

## Success Criteria

### Skeleton Phase (Now)
- ✅ Code compiles and passes static analysis
- ✅ Default behavior (disabled) causes zero performance impact
- ✅ No compilation errors when Ollama URL unreachable
- ✅ Config options documented and tested offline

### Full Implementation Phase (Later)
- ✅ <3s latency for LLM response (acceptable UI delay)
- ✅ Ambiguous matches (70-75% confidence) resolved to 90%+ with LLM
- ✅ LLM explanations readable and useful to user
- ✅ Graceful degradation if Ollama unavailable (falls back to multi-factor)
- ✅ Can be disabled without code changes (config only)

---

## Files Modified / Created

### Skeleton Phase
- `app/Services/LLM/OllamaMatchResolver.php` (new)
- `app/Services/PatternMatcher.php` (modified - add LLM integration point)
- `config/services.php` (new or modified - add Ollama config)
- `.env.example` (modified - add OLLAMA_* vars)
- `tests/Unit/Services/LLM/OllamaMatchResolverTest.php` (new - test stubs)
- `OLLAMA_SKELETON_INTEGRATION.md` (this file - documentation)

### Later (Full Implementation)
- `app/Http/Controllers/AdminController.php` (add health check endpoint)
- `resources/views/admin/health-checks.blade.php` (show Ollama status)
- `app/Console/Commands/OllamaHealthCheck.php` (optional - scheduled check)
- Tests for HTTP integration, response parsing, etc.

---

## Related Documentation

- `MULTI_FACTOR_VS_ML_ANALYSIS.md` - Why Ollama is now Tier 5 (viable)
- `PARTNER_MATCHING_ARCHITECTURE.md` - Overall matching flow (Tier 1)
- Implementation plan for Tier 1 (Phase 1-4): Schema, ScoringEngine, UI refactor
