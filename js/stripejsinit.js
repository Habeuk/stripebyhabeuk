// Initialize Stripe.js using your publishable key
/**
 * @See https://stripe.com/docs/js
 */
if (window.Stripe) {
	(function($, Drupal) {
		function init(context, settings) {
			const configs = settings.stripebyhabeuk;
			once("stripebyhabeuk_add_cart", "#" + configs.idhtml, context).forEach(
				(item) => {
					console.log('configs stripe : ', configs);
					const stripe = window.Stripe(configs.publishableKey);
					// Get tag form container.( Cela implique que les champs de recuperation des informations de la carte soit toujours dans un form. )
					const form = item.closest("form");
					if (!form) {
						console.log('error form is not present');
						return false;
					}
					// si la class secrete existe, alors on se premare pour effectuer le payment.
					if (configs.clientSecret) {
						// si le paiment requiert la methode de paiement.
						if (configs.payment_status == "requires_payment_method") {
							const options = {
								clientSecret: configs.clientSecret,
								// Fully customizable with appearance API.
								appearance: {/*...*/ },
							};
							// Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in a previous step
							const elements = stripe.elements(options);
							// Create and mount the Payment Element
							const paymentElement = elements.create('payment');
							paymentElement.mount(item);
							// losque le client submit le form.
							form.addEventListener("submit", (event) => {
								if (
									form.querySelector("#payment-intent-id" + configs.idhtml)
										.value.length > 0
								) {
									return true;
								}
								// We don't want to let default form submission happen here,
								// which would refresh the page.
								event.preventDefault();
								// on confime le paiment
								stripe.confirmPayment({
									elements,
									confirmParams: {
										// Return URL where the customer should be redirected after the PaymentIntent is confirmed.
										return_url: configs.return_url,
									},
								})
									.then(function(result) {
										if (result.error) {
											// Inform the customer that there was an error.
										}
										console.log(result);
										//alert('verifie les données de confirm paiement');
									}).catch((error) => {
										console.log('error : ', error);

									});
							});
						}
						// le formulaire existe et le status du paiment doit etre different de 'succeeded'.
						else if (configs.payment_status != 'succeeded') {
							console.log('prepare confirm paiemet');
							/**
							 * Il me semble qu'on doit verifier le status du paiement, et aussi passer le formulaire à l'etape suivante( dans le cas de la redirection).
							 */
							form.addEventListener("submit", (event) => {
								if (
									form.querySelector("#payment-intent-id" + configs.idhtml)
										.value.length > 0
								) {
									return true;
								}
								// We don't want to let default form submission happen here,
								// which would refresh the page.
								event.preventDefault();
								// on confime le paiment
								stripe.confirmPayment({
									clientSecret: configs.clientSecret,
									confirmParams: {
										// Return URL where the customer should be redirected after the PaymentIntent is confirmed.
										return_url: configs.return_url,
									},
								})
									.then(function(result) {
										if (result.error) {
											// Inform the customer that there was an error.
										}
										console.log(result);
										//alert('verifie les données de confirm paiement');
									});
							});
						} else {
							if (configs.payment_status === 'succeeded') {
								form.querySelector('[type="submit"][name="op"]').click();
							}
						}
					}
					//
					else {
						/**
						 * On cree les champs pour recuperer les informations de la CB.
						 */
						const elements = stripe.elements();

						// Set up Stripe.js and Elements to use in checkout form						
						const options = {
							hidePostalCode: true,
							style: {
								base: {
									iconColor: '#32325d',
									color: '#32325d',
									fontWeight: '500',
									fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif',
									fontSize: '16px',
									fontSmoothing: 'antialiased',
									':-webkit-autofill': {
										color: '#fce883',
									},
									'::placeholder': {
										color: '#aba3a9',
									},
								},
								invalid: {
									iconColor: '#fb1818',
									color: '#fb1818',
								},
							},
						};
						/**
						 * https://stripe.com/docs/js/elements_object/create_element?type=card
						 */
						const cardElement = elements.create('card', options);
						cardElement.mount(item);
						// console.log('form getElementById : ', form.querySelector("#payment-method-id" + configs.idhtml));
						if (form) {
							form.addEventListener("submit", (event) => {
								if (
									form.querySelector("#payment-method-id" + configs.idhtml)
										.value.length > 0
								) {
									return true;
								}
								// hide bouton 
								form.querySelector('[type="submit"][name="op"]').style.opacity=0.5;
								form.querySelector('[type="submit"][name="op"]').disabled = true;			
								// We don't want to let default form submission happen here,
								// which would refresh the page.
								event.preventDefault();
								stripe.createPaymentMethod({
									type: 'card',
									card: cardElement,
									billing_details: {
										// Include any additional collected billing details.
										// name: 'Jenny Rosen',
										// on ferra la MAJ de la methode sur le serveur avec les informations necessaire.
									},
								}).then(({ paymentMethod, error }) => {
									// Handle result.error or result.paymentMethod
									if (paymentMethod) {
										//Ajouter le payment-method-id dans le form.
										form.querySelector(
											"#payment-method-id" + configs.idhtml
										).value = paymentMethod.id;
										form.querySelector('[type="submit"][name="op"]').disabled = false;	
										// Submit the form.
										form
											.querySelector('[type="submit"][name="op"]')
											.click();
									} else if (error) {
										console.log("error : ", error);
										// On doit voir comment afficher les messages d'erreurs.
									}
								});;

							});
						}
					}
				}
			);
		}
		/**
		 * --
		 */
		Drupal.behaviors.stripebyhabeuk = {
			attach: function(context, settings) {
				init(context, settings);
			},
		};
	})(jQuery, Drupal);
}
