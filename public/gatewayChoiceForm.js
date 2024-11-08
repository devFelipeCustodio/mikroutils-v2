(function () {
  const choiceAll = document.querySelector("input[data-choice-all]");
  const choiceOpts = document.querySelectorAll(
    ".gateway_choice_form .form-check-input:not(input[data-choice-all])",
  );

  if (choiceAll) {
    if ([...choiceOpts].some((el) => el.checked)) choiceAll.checked = false;
  }

  choiceOpts.forEach((el) => {
    if (choiceAll.checked) el.disabled = true;

    el.addEventListener("change", (e) => {
      if (e.target.checked) choiceAll.checked = false;
    });
  });

  choiceAll.addEventListener("change", () => {
    for (const el of choiceOpts) {
      el.disabled = choiceAll.checked;
    }
  });
})();
