(function () {
  var editableSelector = '[data-editable="true"]';
  var activeEditor = null;

  function toPayload(element, value) {
    var payload = new URLSearchParams();
    payload.set("type", element.dataset.editType || "");
    payload.set("content_id", element.dataset.contentId || "");
    payload.set("field", element.dataset.editField || "");
    payload.set("value", value);

    if (element.dataset.blockIndex) {
      payload.set("block_index", element.dataset.blockIndex);
    }

    return payload;
  }

  function saveValue(element, nextValue) {
    return fetch("/editor-mode/save-field", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
      },
      credentials: "same-origin",
      body: toPayload(element, nextValue).toString(),
    }).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.success) {
          var error = (data && data.error) || "Save failed";
          throw new Error(error);
        }

        return data;
      });
    });
  }

  function replaceWithEditor(element) {
    if (activeEditor !== null || !element) {
      return;
    }

    var originalText = element.textContent || "";
    var isTextarea = originalText.indexOf("\n") >= 0;
    var input = document.createElement(isTextarea ? "textarea" : "input");
    input.value = originalText;
    input.className = "editor-inline-input";
    input.setAttribute("aria-label", "Edit field");

    if (!isTextarea) {
      input.type = "text";
    } else {
      input.rows = Math.max(2, originalText.split("\n").length);
    }

    element.classList.add("is-editing");
    element.textContent = "";
    element.appendChild(input);
    input.focus();
    input.select();
    activeEditor = element;

    var commit = function () {
      var value = input.value;
      saveValue(element, value)
        .then(function () {
          element.textContent = value;
          element.classList.remove("is-error");
        })
        .catch(function (error) {
          element.textContent = originalText;
          element.classList.add("is-error");
          window.alert("Editor Mode: " + error.message);
        })
        .finally(function () {
          element.classList.remove("is-editing");
          activeEditor = null;
        });
    };

    var cancel = function () {
      element.textContent = originalText;
      element.classList.remove("is-editing");
      activeEditor = null;
    };

    input.addEventListener("blur", commit, { once: true });
    input.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        event.preventDefault();
        cancel();
        return;
      }

      if (event.key === "Enter" && !isTextarea) {
        event.preventDefault();
        input.blur();
      }
    });
  }

  document.querySelectorAll(editableSelector).forEach(function (element) {
    element.classList.add("editor-editable");
    element.addEventListener("click", function (event) {
      event.preventDefault();
      replaceWithEditor(element);
    });
  });
})();
