  
//   ===============================================================================
//   ===============================================================================

const centeredFormsContainer = document.querySelector(".centered-forms-container"),
    pwShowHide = document.querySelectorAll(".showHidePw"),
    pwFields = document.querySelectorAll(".password"),
    signUp = document.querySelector(".signup-link"),
    login = document.querySelector(".login-link");

    pwShowHide.forEach(eyeIcon =>{
    eyeIcon.addEventListener("click", ()=>{
        pwFields.forEach(pwField =>{
            if(pwField.type ==="password"){
                pwField.type = "text";

                pwShowHide.forEach(icon =>{
                    icon.classList.replace("uil-eye-slash", "uil-eye");
                })
            }else{
                pwField.type = "password";

                pwShowHide.forEach(icon =>{
                    icon.classList.replace("uil-eye", "uil-eye-slash");
                })
            }
        })
    })
})


 
const inputs = document.querySelectorAll('.digit-input');

    inputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        let value = e.target.value;

        // Allow only alphanumeric characters (letters + digits)
        if (!/^[a-zA-Z0-9]$/.test(value)) {
          e.target.value = '';
          input.classList.remove('filled');
          return;
        }

        // Add green border for filled input
        input.classList.add('filled');

        // Enable and focus the next input
        if (index < inputs.length - 1) {
          inputs[index + 1].disabled = false;
          inputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace') {
          if (input.value === '') {
            if (index > 0) {
              inputs[index - 1].focus();
              inputs[index - 1].value = '';
              inputs[index - 1].classList.remove('filled');
              input.disabled = true;
            }
          } else {
            input.value = '';
            input.classList.remove('filled');
          }
        }
      });
    });


signUp.addEventListener("click", ()=>{
    centeredFormsContainer.classList.add("active");
})

login.addEventListener("click", ()=>{
    centeredFormsContainer.classList.remove("active");
})

 
