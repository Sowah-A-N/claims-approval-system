<head>
    <title>Unauthorized Access</title>
</head>
<div class="ghost">
  
<div class="ghost--navbar"></div>
  <div class="ghost--columns">
    <div class="ghost--column">
      <div class="code"></div>
      <div class="code"></div>
      <div class="code"></div>
      <div class="code"></div>
    </div>
    <div class="ghost--column">
      <div class="code"></div>
      <div class="code"></div>
      <div class="code"></div>
      <div class="code"></div>
    </div>
    <div class="ghost--column">
      <div class="code"></div>
      <div class="code"></div>
      <div class="code"></div>
      <div class="code"></div>
    </div>
    
  </div>
  <div class="ghost--main">
    <div class="code"></div>
    <div class="code"></div>

  </div>

</div>

<h1 class="police-tape police-tape--1">
  &nbsp;&nbsp;&nbsp;&nbsp;Error: 403&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Error: 403&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Error: 403&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Error: 403&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Error: 403&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Error: 403&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Error: 403
</h1>
<h1 class="police-tape police-tape--2">Forbidden&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Forbidden&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Forbidden&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Forbidden&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Forbidden&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Forbidden&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h1>

<div class="container">
    <button id="goToLogin">Go back here</button>
  </div>

<script>
    const goToLogin = document.getElementById('goToLogin');
// Assuming goToLogin is the element you want to attach the click event to
    goToLogin.addEventListener('click', function() {
        window.location.href = '../../../index.php'; // Redirect to the login page
    });
</script>

<style>


/* Button Styling */
#goToLogin {
    background-color: #007bff; /* Primary blue color */
    color: #fff; /* White text color */
    padding: 15px 30px; /* Button padding */
    font-size: 16px; /* Font size */
    font-weight: bold; /* Bold text */
    text-transform: uppercase; /* Uppercase text */
    border: none; /* No border */
    border-radius: 50px; /* Rounded corners */
    box-shadow: 0px 8px 15px rgba(0, 123, 255, 0.2); /* Subtle shadow */
    cursor: pointer; /* Pointer cursor on hover */
    transition: all 0.3s ease; /* Smooth transition for hover effects */
}

/* Hover Effect */
#goToLogin:hover {
    background-color: #0056b3; /* Darker blue on hover */
    box-shadow: 0px 15px 20px rgba(0, 86, 179, 0.4); /* More prominent shadow */
    transform: translateY(-3px); /* Slight upward movement */
}

/* Focused and Active States */
#goToLogin:focus, #goToLogin:active {
    outline: none; /* Remove outline */
    box-shadow: 0px 5px 10px rgba(0, 86, 179, 0.4); /* Slight shadow change */
    transform: translateY(-1px); /* Slight downward movement */
}

 /* Flex container for centering */
 .container {
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        @import url('https://fonts.googleapis.com/css?family=Roboto+Condensed:700');

$dark-grey: #111111;
$lighter-grey: #27292d;
$yellow: #e2bb2d;
$yellow2: #B79A2F;

html { 
  height: 100%;
}

body {
  min-height: 100%;
  background-color:$dark-grey;
  font-family: "Roboto Condensed";
  text-transform: uppercase;
  overflow: hidden;  
}

.police-tape {
  background-color: $yellow;
  background: linear-gradient(180deg, lighten($yellow, 20%) 0%, $yellow 5%, $yellow 90%, lighten($yellow, 5%) 95%, darken($yellow, 50%) 100%);
  padding: 0.125em;
  font-size: 3em;
  text-align: center;
  white-space: nowrap;
}

.police-tape--1 {
  transform: rotate(10deg);
  position: absolute;
  top: 40%;
  left: -5%;
  right: -5%;
  z-index: 2;
  margin-top: 0;
}
.police-tape--2 {
  transform: rotate(-8deg);
  position: absolute;
  top: 50%;
  left: -5%;
  right: -5%;
}

.ghost {
  display: flex;
  justify-content: stretch;
  flex-direction: column;
  height: 100vh;
}
.ghost--columns {
  display: flex;
  flex-grow: 1;
  flex-basis: 200px;
  align-content: stretch;
}

.ghost--navbar {
  flex: 0 0 60px;
  background: linear-gradient(0deg, $lighter-grey 0px, $lighter-grey 10px, transparent 10px);
  border-bottom: 2px solid $dark-grey;
}
.ghost--column {
  flex: 1 0 30%;
  border-width: 0px;
  border-style: solid;
  border-color: $lighter-grey;
  border-left-width: 10px;
  background-color: darken($lighter-grey, 6%);
  @for $i from 1 through 3 {
    &:nth-child(#{$i}) {      
      .code {
        @for $j from 1 through 4 {
          &:nth-child(#{$j}) {
            // $rotation: (5 - random(10)) + deg;
            // transform: translateY(0px) rotate($rotation);      
            $spacing: (random(9) / 2) + 1em;
            margin-left: $spacing;
          }
        }
      }
    }
  }
}
.ghost--main {
  background-color: $dark-grey;
  border-top: 15px solid lighten($lighter-grey, 4%);
  flex: 1 0 100px;
}

.code {
  display: block;
  width: 100px;
  background-color: $lighter-grey;
  height: 1em;
  margin: 1em;
  
}
.ghost--main .code {
  height: 2em;
  width: 200px;
}


</style>