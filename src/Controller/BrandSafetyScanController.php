<?php

namespace Drupal\brand_safety_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\key\Entity\Key;
use Drupal\Core\Database\Database;

class BrandSafetyScanController extends ControllerBase {

  public function scan(Request $request): JsonResponse {
    $body = $request->request->get('body');
    if (empty(trim($body))) {
      return new JsonResponse(['error' => 'No content provided.'], 400);
    }

    // ✅ Get OpenAI key from Key module.
    $apiKey = Key::load('openai_key')->getKeyValue();
    if (!$apiKey) {
      return new JsonResponse(['error' => 'OpenAI key not found.'], 500);
    }

    // ✅ Fetch known keywords from DB.
    $conn = Database::getConnection();
    $results = $conn->select('brand_safety_keywords', 'k')
      ->fields('k', ['keyword'])
      ->execute()
      ->fetchCol();

    $known_keywords = array_map('trim', $results);
    $keywords_string = implode(', ', $known_keywords);

    // ✅ Load prompt from config and inject dynamic values.
    $prompt_template = \Drupal::config('brand_safety_manager.settings')->get('ai_prompt') ?? 'Analyze the following content for sensitive terms.';
    $final_prompt = str_replace(
      ['{{KNOWN_KEYWORDS}}', '{{CONTENT}}'],
      [$keywords_string, $body],
      $prompt_template
    );

    \Drupal::logger('brand_safety_manager')->notice('🧠 Prompt used: ' . $final_prompt);

    try {
      $client = \Drupal::httpClient();
      $response = $client->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'gpt-4',
          'temperature' => 0.2,
          'messages' => [
            ['role' => 'system', 'content' => 'You are a brand safety content validator.'],
            ['role' => 'user', 'content' => $final_prompt],
          ],
        ],
      ]);

      $result = json_decode($response->getBody(), true);
      $content = $result['choices'][0]['message']['content'] ?? '';

      // ✅ Attempt to parse result into JSON
      $decoded = json_decode($content, true);
      if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return new JsonResponse(['error' => 'Invalid AI response format. Raw: ' . htmlentities($content)], 500);
      }

      return new JsonResponse([
        'result' => [
          'matched_keywords' => $decoded['matched_keywords'] ?? [],
          'suggested_keywords' => $decoded['suggested_keywords'] ?? [],
          'risk_level' => $decoded['risk_level'] ?? 'Unknown',
          'explanation' => $decoded['explanation'] ?? 'No explanation provided.',
        ]
      ]);

    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'OpenAI API Error: ' . $e->getMessage()], 500);
    }
  }

}
