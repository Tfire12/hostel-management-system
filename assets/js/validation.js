function validateForm() {
    let name = document.forms["studentForm"]["name"].value;
    let reg = document.forms["studentForm"]["reg_number"].value;
    let email = document.forms["studentForm"]["email"].value;

    if (name === "" || reg === "" || email === "") {
        alert("All fields must be filled out");
        return false;
    }
    return true;
}
