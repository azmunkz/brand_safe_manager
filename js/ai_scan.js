(function ($, Drupal, once) {
  Drupal.behaviors.aiScanButtonBehavior = {
    attach: function (context, settings) {
      const elements = once('ai-scan-button', '#ai-scan-button', context);
      if (!elements.length) return;

      const $scanButton = $(elements[0]);

      $scanButton.on('click', function (e) {
        e.preventDefault();

        let bodyContent = '';

        // Try CKEditor 5 DOM wrapper
        const ckeditorEditable = document.querySelector('.ck-editor__editable');
        if (ckeditorEditable) {
          bodyContent = ckeditorEditable.innerHTML.trim();
        }

        // Fallback to textarea
        if (!bodyContent) {
          bodyContent = $('textarea[name="body[0][value]"]', context).val().trim();
        }

        if (!bodyContent) {
          Swal.fire({
            icon: 'error',
            title: 'Empty Content',
            text: 'Please enter content before scanning.',
          });
          return;
        }

        Swal.fire({
          title: 'Scanning...',
          text: 'AI is analyzing your content.',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        $.ajax({
          url: '/brand-safety/scan',
          type: 'POST',
          data: { body: bodyContent },
          success: function (response) {
            console.log('AJAX Response: ', response)

            const result = response.result;

            if (typeof result === 'object') {
              const html = `
                <dl class="result-display">
                    <dt>Risk Level:</dt>
                    <dd>${result.risk_level || 'Unknown'}</dd>
                    <dt>Matched Keywords:</dt>
                    <dd>${result.matched_keywords?.join(', ') || '-'}</dd>
                    <dt>Suggested Keywords:</dt>
                    <dd>${result.suggested_keywords?.join(', ') || '-'}</dd>
                    <dt>Explanation:</dt>
                    <dd>${result.explaination || '-'}</dd>
                </dl>
              `;

              Swal.fire({
                icon: result.risk_level === 'High' ? 'warning' : 'info',
                title: 'AI Scan Result',
                html: html,
                width: '50em',
              });

            } else {
              Swal.fire({
                icon: 'error',
                title: 'Invalid Result',
                text: 'The AI response format is unexpected'
              });
            }
          },
          error: function () {
            Swal.fire({
              icon: 'error',
              title: 'Scan Failed',
              text: 'Something went wrong during scanning.',
            });
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);
