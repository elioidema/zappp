export default function (context) {
    // context.userAgent = process.server ? context.req.headers['user-agent'] : navigator.userAgent
    // console.log('isLoggedIn.js');
    // console.log(context);

    if (!context.store.state.user) {
        console.log('not logged in');
    }
}