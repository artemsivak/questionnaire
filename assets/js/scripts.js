function getAllConditions(thisName, thisVal) {
    $("div.field-group[data-cond='1']").each(function() {
        var condition = $(this).data("condition");
        var name = condition.split(" : ")[0];
        var value = condition.split(" : ")[1];
        console.log("name - "+name+" | value - "+value);
        if (thisName == name && thisVal == value) {
            $(this).addClass("show");
            if ($(this).data("required") == "required") {
                $(this).find(" > *").attr("required", "required");
            }
        }else{
            $(this).removeClass("show");
            $(this).find(" > *").removeAttr("required");
            $(this).find(" > *").val("");
        }
    });
}

function validPass(pass) {
    if (!$("#"+pass).val().match(/[a-z]/g)) { return false; }
    if (!$("#"+pass).val().match(/[0-9]/g)) { return false; }
    if ($("#"+pass).val().length < 8 || $("#"+pass).val().length > 14) { return false; }
    return true;
}

function validConfirm(pass, confirm) {
    if ($("#"+pass).val() != $("#"+confirm).val()) { return false; }
    else{ return true; }
}

function validateEmail(email) {
    var email = $("#"+email).val();
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function validateSpanish() {
    $("div#ConstructorBlock form").submit(function(e) {
        var i_agree = $("#SpanishUnderstand").val();
        if (i_agree != "I have read, understand and acknowledged all the above statements") {
            alert("Please type the correct statement");
            e.preventDefault();
            return false;
        }
    });
}

function verifyPassword(pass) {

    v1 = '<div id="message" class="ValidNotification" style="display: none;">';
        v2 = '<h5>Password must contain:</h5>';
        v3 = '<p id="letter" class="invalid">A <b>lowercase</b> letter</p>';
        v4 = '<p id="number" class="invalid">A <b>number</b></p>';
        v5 = '<p id="leng" class="invalid">Minimum <b>8 characters</b>, max 15</p>';
    v6 = '</div>';

    validBlock = v1+v2+v3+v4+v5+v6;

    c1 = '<div id="confirm_validation" class="ValidNotification" style="display: none;">';
        c2 = '<h5>Password and Confirm password should match.</h5>';
    c3 = '</div>';

    confirmValidBlock = c1 + c2 + c3;

    e1 = '<div id="email_validation" class="ValidNotification" style="display: none;">';
        e2 = '<h5>Email is not valid.</h5>';
    e3 = '</div>';

    emailValidation = e1 + e2 + e3;

    d1 = '<div id="date_validation" class="ValidNotification" style="display: none;">';
        d2 = '<h5>You should be more than 18 less than 90 years old.</h5>';
    d3 = '</div>';

    dateValidation = d1 + d2 + d3;

    $("#"+pass).parent().append(validBlock);
    $("#confirm_password").parent().append(confirmValidBlock);
    $("#Email").parent().append(emailValidation);
    $("#lv_dateofbirth").parent().append(dateValidation);

    var myInput = document.getElementById(pass);
    myInput.onfocus = function() {
          document.getElementById("message").style.display = "block";
    }
    myInput.onblur = function() {
          document.getElementById("message").style.display = "none";
    }

    myInput.onkeyup = function() {
         if(myInput.value.match(/[a-z]/g)) { 
            letter.classList.remove("invalid"); letter.classList.add("valid");
          }else{ letter.classList.remove("valid"); letter.classList.add("invalid"); }
          if(myInput.value.match(/[0-9]/g)) { 
            number.classList.remove("invalid"); number.classList.add("valid");
          }else{ number.classList.remove("valid"); number.classList.add("invalid"); }
          if(myInput.value.length > 7 && myInput.value.length < 15) {
            leng.classList.remove("invalid"); leng.classList.add("valid");
          }else{ leng.classList.remove("valid"); leng.classList.add("invalid");}
    }

    $("div#ConstructorBlock form").submit(function(e) {

        if (!validPass("password")) {
            $("#message").show();
            e.preventDefault();
            return false;
        }else{
            $("#message").hide();
        }
    
        if(!validConfirm("password", "confirm_password")) {
            $("#confirm_validation").show();
            e.preventDefault();
            return false;
        }else{
            $("#confirm_validation").hide();
        }
        
        if(!validateEmail("Email")) {
            $("#email_validation").show();
            $('#Email').focus().select();
            e.preventDefault();
            return false;
        }else{
            $("#email_validation").hide(); 
        }
        
        var dob = $("#lv_dateofbirth").val();
        var year = Number(dob.substring(0,4));
        var month = Number(dob.substring(5,7)) - 1;
        var day = Number(dob.substring(8,10));
        var today = new Date();
        var age = today.getFullYear() - year;
        if (today.getMonth() < month || (today.getMonth() == month && today.getDate() < day)) {
            age--;
        }

        if(age < 18) {
            $("#date_validation").show();
            $('#lv_dateofbirth').focus().select();
            e.preventDefault();
            return false;
        } else if(age > 89){
            $('#lv_dateofbirth').focus().select();
            $("#date_validation").show();
            e.preventDefault();
            return false;
        }else{
            $("#date_validation").hide();
        }
                    
    });
}

$(document).ready(function() {
    $(".hasExpl > h4 > i").hover(function() {
        $(this).closest("div").find("> div").attr("style", "display: block");
    }, function() {
        $(this).closest("div").find("> div").attr("style", "display: none");
    });
    
    $("div.field-group[data-cond='1']").each(function() {
        var is_required = $(this).find(" > *").attr("required");
        if (is_required == "required") {
            $(this).attr("data-required", "required");
            $(this).find(" > *").removeAttr("required");
        }
        var name = $(this).data("condition").split(" : ")[0];
        $("input[name='"+ name +"']").addClass("hasCondition");
        $("select[name='"+ name +"']").addClass("hasCondition");
    });

    $("div.field-group .hasCondition").change(function() {
        val = $(this).val();
        name = $(this).attr("name");
        getAllConditions(name, val);
    });

    var stage = $("input[name='stage']").val();
    if (stage == 1) {
        verifyPassword("password");
    }    

});

