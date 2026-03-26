describe('Navigation & Footer Links', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('has no dead # links in the footer', () => {
    cy.get('footer a[href]').each(($link) => {
      const href = $link.attr('href')
      // Allow anchor links to page sections (e.g. #about, #testimonials)
      if (href.startsWith('#')) {
        expect(href.length).to.be.greaterThan(1, `Footer link "${$link.text()}" should not be a bare # anchor`)
      }
    })
  })

  it('navbar login link navigates to login page', () => {
    cy.get('a[href="/auth/login.php"]').first().click()
    cy.url().should('include', '/auth/login.php')
  })

  it('navbar signup link navigates to signup page', () => {
    cy.visit('/')
    cy.get('a[href="/auth/signup.php"]').first().click()
    cy.url().should('include', '/auth/signup.php')
  })

  it('footer contains working admission links', () => {
    cy.get('footer a[href="/auth/signup.php"]').should('have.length.at.least', 4)
  })

  it('footer Student Portal link goes to login', () => {
    cy.get('footer a[href="/auth/login.php"]').should('exist')
  })
})
