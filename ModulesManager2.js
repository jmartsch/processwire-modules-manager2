$(document).on("click", "#app .pw-panel", function (e, el) {
    e.preventDefault();
    let toggler = $(this);
    pwPanels.addPanel(toggler);
    toggler.click();
});