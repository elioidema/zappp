<template>
  <div class="container min-h-screen flex justify-center items-center text-center mx-auto">
    <form class="bg-primary text-center w-1/3 px-3 py-4 text-white mx-auto rounded">
      <input type="text" placeholder="Username" class="block w-full mx-auto text-sm py-2 px-3 rounded" />
      <input type="text" placeholder="Password" class="block w-full mx-auto text-sm py-2 px-3 rounded my-3" />
      <button v-on:click.prevent="postLogin()" class="bg-blue text-white font-bold py-2 px-4 rounded border block mx-auto w-full">
        Login
      </button>
    </form>
  </div>
</template>

<script>

export default {

  methods: {
    postLogin() {
      console.log('test 11112233');
    }
  }
}
</script>