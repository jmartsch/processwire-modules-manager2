<template>
  <a :href="link" :class="buttonClass" @click.prevent="executeAction">
    <i :class="icon"></i>
    {{ buttonText }}
  </a>
</template>

<script>
  export default {
    props: ["action", "module"],
    methods: {
      executeAction(event) {
        UIkit.notification.closeAll();
        let url = event.target.href;
        if (this.action === "install" || this.action === "downloadAndInstall") {
          vm.isLoading = true;
          this.$http
            .get(url, {
              headers: {"X-Requested-With": "XMLHttpRequest"}
            })
            .then(result => {
              // result.status = 200;
              if (result.status === 200) {
                vm.isLoading = false;
                vm.loadData();
                // this.$emit('loadData');
                if (result.data.message) {
                  UIkit.notification(result.data.message, {
                    status: result.data.status,
                    timeout: 2000
                  });
                } else {
                  UIkit.modal.alert("no correct result message was returned from Modules Manager 2");
                }
              } else {
                vm.isLoading = false;
                UIkit.modal.alert("Error");
              }
            })
            .catch(error => {
              // this.isError = true;
              vm.isLoading = false;
              UIkit.modal.alert("Error: " + error);
            });
        }
        if (this.action === 'uninstall' || this.action === 'update' || this.action === 'delete') {
          let confirmText;
          if (this.action === 'uninstall') {
            confirmText = `Do you really want to uninstall the module ${this.module.title}?`;
          }
          if (this.action === 'update') {
            confirmText = `Do you really want to update the module ${this.module.title}?`;
          }
          if (this.action === 'delete') {
            confirmText = `Do you really want to remove the module ${this.module.title}?`;
          }
          UIkit.modal.confirm(confirmText).then(ok => {
            vm.isLoading = true;
            this.$http
              .get(url, {
                headers: {"X-Requested-With": "XMLHttpRequest"}
              })
              .then(result => {
                if (result.status === 200) {
                  vm.isLoading = false;
                  vm.loadData();
                  if (result.data.message) {
                    UIkit.notification(result.data.message, {
                      status: result.data.status,
                      timeout: 2000
                    });

                  } else {
                    UIkit.modal.alert("no correct result message was returned from Modules Manager 2");
                  }
                } else {
                  vm.isLoading = false;
                  UIkit.modal.alert("Error");
                }
              })
              .catch(error => {
                // this.isError = true;
                vm.isLoading = false;
                UIkit.modal.alert("Error: " + error);
              });
          }, () => {
            // UIkit.modal.alert("Error: " );
          });
        }
      }
    },
    data: function () {
      let link = "./"; // here we either need the absolute link to the pw root, or go up two levels
      let icon = "";
      let buttonText = "";
      let buttonClass =
        "uk-button uk-button-small ";
      if (this.action === "install") {
        link += "install/?&modal=1&class=" + this.module.class_name;
        buttonClass += "uk-button-primary";
        icon = "fa fa-plug";
        buttonText = 'Install';
      }
      if (this.action === "update") {
        link += "download/?&modal=1&class=" + this.module.class_name;
        buttonClass += "confirm uk-button-secondary";
        icon = "fa fa-arrow-circle-up";
        buttonText = `Update to ${this.module.remote_version}`;
      }
      if (this.action === "uninstall") {
        link += "uninstall/?&modal=1&execute=true&class=" + this.module.class_name;
        buttonClass += "confirm uk-button-primary uk-button-danger";
        icon = "fa fa-power-off";
        buttonText = 'Uninstall';
      }
      if (this.action === "delete") {
        link += "delete/?&modal=1&class=" + this.module.class_name;
        buttonClass += "confirm uk-button-primary uk-button-danger";
        icon = "fa fa-trash";
        buttonText = 'Delete files';
      }
      if (this.action === "settings") {
        // watch out. for the settings we have to use the name parameter instead of class
        link = "../../module/edit?name=" + this.module.class_name + "&collapse_info=1&modal=1&";
        buttonClass += "confirm uk-button-default pw-panel pw-panel-reload";
        icon = "fa fa-cog";
        buttonText = 'Settings';

      }
      if (this.action === "downloadAndInstall") {
        link +=
          "download/?url=" +
          this.module.download_url +
          "&class=" +
          this.module.class_name;
        buttonClass += "uk-button-primary";
        icon = "fa fa-download";
        buttonText = 'Download and install';
      }

      return {
        link: link,
        buttonClass: buttonClass,
        icon: icon,
        buttonText: buttonText,
      };
    },
    // mounted() {
    //     $(document).on("click", ".pw-panel", function (e) {
    //         if (typeof pwPanels !== "undefined") {
    //             let toggler = $(e.target);
    //             pwPanels.addPanel(toggler);
    //             toggler.click();
    //         } else {
    //             UIkit.modal.alert("Normally a ProcessWire panel with the module's settings would be opened now");
    //         }
    //     });
    // }
  };
</script>
