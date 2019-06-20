<template>
  <div class="container">
    <form class="bg-primary text-center w-1/3 px-3 py-4 text-white mx-auto rounded">
      <input type="text" placeholder="Username" class="block w-full mx-auto text-sm py-2 px-3 rounded" />
      <input type="text" placeholder="Password" class="block w-full mx-auto text-sm py-2 px-3 rounded my-3" />
      <button class="bg-blue text-white font-bold py-2 px-4 rounded border block mx-auto w-full">
        Login
      </button>
    </form>
  </div>
</template>

<script>
import Logo from '~/components/Logo.vue'

export default {
  components: {
    Logo
  }
}
</script>

<style>
/* Sample `apply` at-rules with Tailwind CSS
.container {
  @apply min-h-screen flex justify-center items-center text-center mx-auto;
}
*/
.container {
  margin: 0 auto;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
}

.title {
  font-family: 'Quicksand', 'Source Sans Pro', -apple-system, BlinkMacSystemFont,
    'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  display: block;
  font-weight: 300;
  font-size: 100px;
  color: #35495e;
  letter-spacing: 1px;
}

.subtitle {
  font-weight: 300;
  font-size: 42px;
  color: #526488;
  word-spacing: 5px;
  padding-bottom: 15px;
}

.links {
  padding-top: 15px;
}
</style>
