<template>
  <div class="container min-h-screen flex justify-center items-center text-center mx-auto">
    <form  v-on:submit.prevent="postLogin()" class="bg-primary text-center w-1/3 px-3 py-4 text-white mx-auto rounded">
      <input type="text" placeholder="Username" class="block w-full mx-auto text-sm py-2 px-3 rounded" />
      <input type="text" placeholder="Password" class="block w-full mx-auto text-sm py-2 px-3 rounded my-3" />
      <button class="bg-blue text-white font-bold py-2 px-4 rounded border block mx-auto w-full">
        Login
      </button>
      <button class="bg-secondary text-white font-bold py-2 px-4 rounded border block mx-auto w-full">
        <router-link to='/profile'>Registreren</router-link>
      </button>
    </form>
  </div>
</template>

<script>

export default {

  methods: {
    postLogin() {
      console.log('test 11112233');
        axios.post('http://192.168.8.118:8080/api/partyaccount/signIn', { login: 'w.kreischer@gmail.com', password: 'asdfasdf'})
        .then((response) => {
          console.log(response)
        })
        .catch((error) => {
          console.log(error.response, 'error ville')
        })
    }
  }
}
</script>