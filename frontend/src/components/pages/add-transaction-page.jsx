import { useState } from 'react'
import { Navbar } from "../molecules/navbar"
import { TransactionForm } from "../molecules/transaction-form"
import { ProtectedRoute } from "../molecules/protected-route"
import transactionService from "../../services/transactionService"
import "../../styles/components/pages.css"

export function AddTransactionPage() {
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [successMessage, setSuccessMessage] = useState('')

  const clearMessages = () => {
    setError('')
    setSuccessMessage('')
  }

  const handleAddTransaction = async (formData) => {
    try {
      setIsLoading(true)
      clearMessages()
      
      // Validation côté client
      const validation = transactionService.validateTransactionData(formData)
      if (!validation.isValid) {
        setError('Données invalides: ' + Object.values(validation.errors).join(', '))
        return
      }

      const response = await transactionService.createTransaction(formData)
      
      if (response.success) {
        setSuccessMessage('Transaction ajoutée avec succès !')
        // Rediriger vers la liste des transactions après un délai
        setTimeout(() => {
          window.location.href = '/transactions' // ou selon votre routage
        }, 2000)
      }
    } catch (err) {
      console.error('Erreur ajout transaction:', err)
      setError(err.message || 'Erreur lors de l\'ajout de la transaction')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <ProtectedRoute>
      <AddTransactionPageContent 
        isLoading={isLoading}
        error={error}
        successMessage={successMessage}
        onAddTransaction={handleAddTransaction}
      />
    </ProtectedRoute>
  )
}

function AddTransactionPageContent({ isLoading, error, successMessage, onAddTransaction }) {
  return (
    <div className="page">
      {/* Navbar */}
      <Navbar currentPage="add-transaction" />
      
      {/* Contenu principal */}
      <main className="page__main">
        <div className="page__container page__container--transaction">
          
          {/* En-tête */}
          <div className="page__header">
            <div className="page__logo">
              <span className="page__logo-text">💰</span>
            </div>
            <h1 className="page__title">
              Nouvelle Transaction
            </h1>
            <p className="page__subtitle">
              Ajoutez une nouvelle transaction à votre portefeuille
            </p>
          </div>
          
          {/* Messages d'erreur et de succès */}
          {error && (
            <div className="alert alert-danger mb-4 p-3 border border-red-400 bg-red-50 text-red-700 rounded-md">
              <div className="flex items-center">
                <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
                <span>{error}</span>
              </div>
            </div>
          )}
          
          {successMessage && (
            <div className="alert alert-success mb-4 p-3 border border-green-400 bg-green-50 text-green-700 rounded-md">
              <div className="flex items-center">
                <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                <span>{successMessage}</span>
              </div>
            </div>
          )}
          
          {/* Formulaire dans une carte */}
          <div className="page__card">
            <TransactionForm 
              onSubmit={onAddTransaction}
              isLoading={isLoading}
              className=""
            />
          </div>
          
          {/* Navigation */}
          <div className="transaction-page__navigation">
            <div className="flex justify-between items-center">
              <a 
                href="/transactions" 
                className="text-[#C38EF0] hover:text-[#B570E8] font-medium hover:underline transition-colors duration-200 flex items-center"
              >
                <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clipRule="evenodd" />
                </svg>
                Voir mes transactions
              </a>
              
              <a 
                href="/dashboard" 
                className="text-gray-600 hover:text-gray-800 font-medium hover:underline transition-colors duration-200"
              >
                Retour au tableau de bord
              </a>
            </div>
          </div>
          
          {/* Aide */}
          <div className="transaction-page__help">
            <p className="transaction-page__help-text">
              Besoin d'aide ?{" "}
              <a href="/support" className="transaction-page__help-link">
                Consultez notre guide
              </a>
            </p>
          </div>
        </div>
      </main>

      {/* Décoration de fond */}
      <div className="page__bg-decoration">
        <div className="page__bg-blob-1"></div>
        <div className="page__bg-blob-2"></div>
      </div>
    </div>
  )
}

export default AddTransactionPage