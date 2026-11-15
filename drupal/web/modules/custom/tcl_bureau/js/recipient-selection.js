(function (Drupal, once) {
  Drupal.behaviors.tclRecipientSelection = {
    attach(context) {
      for (const container of once('tcl-recipient-selection', '[data-recipient-selection]', context)) {
        const list = container.closest('form').querySelector('[data-recipient-list]');
        if (!list) continue;
        const addresses = [...list.querySelectorAll('input[type="checkbox"]:enabled')];
        if (!addresses.length) continue;

        const label = document.createElement('label');
        const toggle = document.createElement('input');
        toggle.type = 'checkbox';
        label.append(toggle, ' Tout cocher');
        const count = document.createElement('span');
        count.setAttribute('role', 'status');
        container.append(label, count);

        function refresh() {
          const selected = addresses.filter(address => address.checked).length;
          toggle.checked = selected === addresses.length;
          toggle.indeterminate = selected > 0 && selected < addresses.length;
          count.textContent = ' (' + selected + ' sur ' + addresses.length + ')';
        }
        toggle.addEventListener('change', () => {
          for (const address of addresses) address.checked = toggle.checked;
          refresh();
        });
        list.addEventListener('change', refresh);
        refresh();
      }
    },
  };
})(Drupal, once);
